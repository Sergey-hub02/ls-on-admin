<?php

namespace Api\Controllers;

require_once __DIR__ . "/Controller.php";
require_once __DIR__ . "/../../config/Connection.php";
require_once __DIR__ . "/../../vendor/autoload.php";

use PDO;
use PDOException;
use Config\Connection;
use PHPSQLParser\PHPSQLParser;

class QueryController extends Controller {
  private PDO $connection;
  private PHPSQLParser $parser;

  /**
   * Выделяет из запроса название базы данных
   * @param string $query
   * @return string
   */
  private function getDatabaseName(string $query): string {
    $parsed = $this->parser->parse($query);
    $dbName = "";

    if (!empty($parsed["SELECT"]) || !empty($parsed["DELETE"])) {
      $dbName = $parsed["FROM"][0]["no_quotes"]["parts"][0];
    }
    else if (!empty($parsed["INSERT"])) {
      $dbName = $parsed["INSERT"][1]["no_quotes"]["parts"][0];
    }
    else if (!empty($parsed["UPDATE"])) {
      $dbName = $parsed["UPDATE"][0]["no_quotes"]["parts"][0];
    }

    return $dbName;
  }

  /**
   * Проверяет, принадлежит ли база данных указанному пользователю
   * @param string $dbName
   * @param int $userId
   * @return bool
   */
  private function checkUserDatabase(string $dbName, int $userId): bool {
    $query = "SELECT * FROM `Database` WHERE `user_id` = ?";
    $stmt = $this->connection->prepare($query);

    if (!$stmt->execute([$userId])) {
      return false;
    }

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($result as $row) {
      if ($row["name"] === $dbName) {
        return true;
      }
    }

    return false;
  }

  /**
   * Задаёт подключение к БД
   */
  public function __construct() {
    $this->connection = (new Connection())->getConnection();
    $this->parser = new PHPSQLParser();
  }

  /**
   * Выполняет пользовательский запрос к базе данных
   * @return void
   */
  public function executeAction(): void {
    $method = strtoupper($_SERVER["REQUEST_METHOD"]);

    if ($method !== "POST") {
      $this->sendOutput(
        json_encode([
          "error" => "Метод $method не поддерживается!",
        ], JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 422 Unprocessable Entity"]
      );
      return;
    }

    // получаем тело запроса
    $data = json_decode(
      file_get_contents("php://input"),
      true
    );

    $query = trim($data["query"]);
    $user = $data["user_id"];

    if (
      empty($query)
      || empty($data["user_id"])
    ) {
      $this->sendOutput(
        json_encode([
          "error" => "Неполные данные! Невозможно выполнить запрос!",
        ], JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 400 Bad Request"]
      );
      return;
    }

    // пользователь может выполнять запросы только к своим базам данных
    if (!$this->checkUserDatabase($this->getDatabaseName($query), $user)) {
      $this->sendOutput(
        json_encode([
          "error" => "Невозможно выполнить запрос! Это не ваша база данных!",
        ], JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 400 Bad Request"]
      );
    }

    $type = array_keys($this->parser->parse($query))[0];

    try {
      $stmt = $this->connection->query($query);

      if ($type === "SELECT") {
        if (!($result = $stmt->fetchAll(PDO::FETCH_ASSOC))) {
          $this->sendOutput(
            json_encode([
              "error" => $stmt->errorInfo(),
            ], JSON_UNESCAPED_UNICODE),
            ["Content-Type: application/json", "HTTP/1.1 500 Internal Server Error"]
          );
          return;
        }

        $this->sendOutput(
          json_encode($result, JSON_UNESCAPED_UNICODE),
          ["Content-Type: application/json", "HTTP/1.1 200 OK"]
        );
        return;
      }

      $this->sendOutput(
        json_encode([
          "message" => "Запрос успешно выполнен!",
        ], JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 200 OK"]
      );
    }
    catch (PDOException $e) {
      $this->sendOutput(
        json_encode([
          "error" => $e->getMessage(),
        ], JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 500 Internal Server Error"]
      );
    }
  }
}