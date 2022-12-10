<?php

namespace Api\Controllers;

require_once __DIR__ . "/Controller.php";
require_once __DIR__ . "/../../config/Connection.php";
require_once __DIR__ . "/../../config/DBConnection.php";
require_once __DIR__ . "/../models/Database.php";
require_once __DIR__ . "/../models/User.php";
require_once __DIR__ . "/../dao/DatabaseDAO.php";

use Config\Connection;
use Config\DBConnection;
use Api\Models\Database;
use Api\Models\User;
use Api\Dao\DatabaseDAO;

class DatabaseController extends Controller {
  private DatabaseDAO $databaseDAO;

  /**
   * Создаёт контроллер для работы с конечными точками /api/databases
   */
  public function __construct() {
    $this->databaseDAO = new DatabaseDAO(
      (new Connection())->getConnection(),
      (new DBConnection())->getConnection()
    );
  }

  /**
   * GET /api/databases/list: возвращает список баз данных
   * GET /api/databases/list?id=N: возвращает базу данных с указанным ID
   * GET /api/databases/list?user_id=N: возвращает базы данных, принадлежащие пользователю с id=N
   * @return void
   */
  public function listAction(): void {
    $method = strtoupper($_SERVER["REQUEST_METHOD"]);
    $params = $this->getQueryStringParams();

    // для данных конечных точек можно применять только GET запросы
    if ($method !== "GET") {
      $this->sendOutput(
        json_encode([
          "error" => "Метод $method не поддерживается!",
        ], JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 422 Unprocessable Entity"]
      );
      return;
    }

    // нужна только одна база данных
    if (!empty($params["id"])) {
      $database = $this->databaseDAO->readOne($params["id"]);

      // пользователь с заданным id не найден
      if ($database === null) {
        $this->sendOutput(
          json_encode([
            "error" => "База данных с id {$params['id']} не была найдена!",
          ], JSON_UNESCAPED_UNICODE),
          ["Content-Type: application/json", "HTTP/1.1 400 Bad Request"]
        );
        return;
      }

      // база данных с заданным id найдена
      $this->sendOutput(
        json_encode($database->toArray(), JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 200 OK"]
      );
      return;
    }

    // нужны базы данных, принадлежащие конкретному пользователю
    if (!empty($params["user_id"])) {
      $databases = $this->databaseDAO->getUserDatabases($params["user_id"]);

      // базы данных пользователя не были найдены
      if (empty($databases)) {
        $this->sendOutput(
          json_encode([
            "error" => "Базы данных пользователя с id = {$params['user_id']} не были найдены!",
          ], JSON_UNESCAPED_UNICODE),
          ["Content-Type: application/json", "HTTP/1.1 400 Bad Request"]
        );
        return;
      }

      // конвертируем все объекты в ассоциативные массивы
      $databases = array_map(function (Database $database) {
        return $database->toArray();
      }, $databases);

      // база данных с заданным id найдена
      $this->sendOutput(
        json_encode($databases, JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 200 OK"]
      );
      return;
    }

    // конвертируем все объекты в ассоциативные массивы
    $databases = array_map(function (Database $database) {
      return $database->toArray();
    }, $this->databaseDAO->readAll());

    // конвертируем массивы в JSON-строки и отправляем ответ
    $this->sendOutput(
      json_encode($databases, JSON_UNESCAPED_UNICODE),
      ["Content-Type: application/json", "HTTP/1.1 200 OK"]
    );
  }

  /**
   * POST /api/databases/create: создаёт базу данных и добавляет её в БД
   * @return void
   */
  public function createAction(): void {
    $method = strtoupper($_SERVER["REQUEST_METHOD"]);

    // для данной конечной точки применяется только метод POST
    if ($method !== "POST") {
      $this->sendOutput(
        json_encode([
          "error" => "Метод $method не поддерживается!"
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

    // проверяем полноценность данных
    if (
      empty($data["name"])
      || empty($data["user_id"])
    ) {
      $this->sendOutput(
        json_encode([
          "error" => "Невозможно выполнить запрос! Неполные данные!"
        ], JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 400 Bad Request"]
      );
      return;
    }

    // все данные для создания записи присутствуют
    $database = new Database();
    $user = new User();

    $database->setName($data["name"]);
    $user->setUserId($data["user_id"]);
    $database->setUser($user);

    $created = $this->databaseDAO->create($database);

    // произошла ошибка при добавлении базы данных
    if ($created === null) {
      $this->sendOutput(
        json_encode([
          "error" => "Ошибка добавления базы данных!"
        ], JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 500 Internal Server Error"]
      );
      return;
    }

    // добавление базы данных прошло успешно
    $this->sendOutput(
      json_encode($created->toArray(), JSON_UNESCAPED_UNICODE),
      ["Content-Type: application/json", "HTTP/1.1 201 Created"]
    );
  }

  /**
   * DELETE /api/databases/delete: удаляет базу данных из БД
   * @return void
   */
  public function deleteAction(): void {
    $method = strtoupper($_SERVER["REQUEST_METHOD"]);

    // для данной конечной точки можно применять только метод DELETE
    if ($method !== "DELETE") {
      $this->sendOutput(
        json_encode([
          "error" => "Метод $method не поддерживается!"
        ], JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 422 Unprocessable Entity"]
      );
      return;
    }

    $params = $this->getQueryStringParams();

    // id базы данных обязательно должен быть задан
    if (empty($params["id"])) {
      $this->sendOutput(
        json_encode([
          "error" => "Невозможно удалить базу данных! Не задан id базы данных!"
        ], JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 400 Bad Request"]
      );
      return;
    }

    // ошибка при удалении базы данных
    if (!$this->databaseDAO->delete($params["id"])) {
      $this->sendOutput(
        json_encode([
          "error" => "Произошла ошибка при удалении базы данных!"
        ], JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 500 Internal Server Error"]
      );
      return;
    }

    // успешное удаление
    $this->sendOutput(
      json_encode([
        "message" => "Удаление базы данных прошло успешно!"
      ], JSON_UNESCAPED_UNICODE),
      ["Content-Type: application/json", "HTTP/1.1 200 OK"]
    );
  }
}