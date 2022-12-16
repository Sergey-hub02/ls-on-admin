<?php

namespace Api\Controllers;

require_once __DIR__ . "/Controller.php";
require_once __DIR__ . "/../../config/Connection.php";
require_once __DIR__ . "/../../config/DBConnection.php";

require_once __DIR__ . "/../models/Table.php";
require_once __DIR__ . "/../models/Column.php";

require_once __DIR__ . "/../dao/TableDAO.php";

use Config\Connection;
use Config\DBConnection;
use Api\Models\Table;
use Api\Models\Column;
use Api\Dao\TableDAO;

class TableController extends Controller {
  private TableDAO $tableDAO;

  /**
   * Создаёт контроллер для работы с конечными точками /api/tables
   */
  public function __construct() {
    $this->tableDAO = new TableDAO(
      (new Connection())->getConnection(),
      (new DBConnection())->getConnection()
    );
  }

  /**
   * GET /api/tables/list: возвращает список таблиц
   * GET /api/tables/list?id=N: возвращает таблицу с указанным id
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

    // нужна только 1 таблица
    if (!empty($params["id"])) {
      $table = $this->tableDAO->readOne($params["id"]);

      // таблица с заданным id не найдена
      if ($table === null) {
        $this->sendOutput(
          json_encode([
            "error" => "Таблица с id {$params['id']} не была найдена!",
          ], JSON_UNESCAPED_UNICODE),
          ["Content-Type: application/json", "HTTP/1.1 400 Bad Request"]
        );
        return;
      }

      // таблица с заданным id найдена
      $this->sendOutput(
        json_encode($table->toArray(), JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 200 OK"]
      );
      return;
    }

    // нужны таблицы определённой базы данных
    if (!empty($params["database_id"])) {
      $tables = $this->tableDAO->getDatabaseTables(intval($params["database_id"]));

      if (empty($tables)) {
        $this->sendOutput(
          json_encode([]),
          ["Content-Type: application/json", "HTTP/1.1 200 OK"]
        );

        return;
      }

      $tables = array_map(function (Table $table) {
        return $table->toArray();
      }, $tables);

      $this->sendOutput(
        json_encode($tables, JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 200 OK"]
      );
      return;
    }

    // конвертируем все объекты в ассоциативные массивы
    $tables = array_map(function (Table $table) {
      return $table->toArray();
    }, $this->tableDAO->readAll());

    // конвертируем массивы в JSON-строки и отправляем ответ
    $this->sendOutput(
      json_encode($tables, JSON_UNESCAPED_UNICODE),
      ["Content-Type: application/json", "HTTP/1.1 200 OK"]
    );
  }

  /**
   * POST /api/tables/create: создаёт таблицу и добавляет её в БД
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
      || empty($data["database_id"])
      || empty($data["columns"])
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
    $table = new Table();

    $table->setName($data["name"]);
    $table->setDatabaseId($data["database_id"]);

    $columns = [];
    foreach ($data["columns"] as $arColumn) {
      $column = new Column(
        $arColumn["name"],
        $arColumn["type"],
        $arColumn["null"],
        $arColumn["primary"],
        $arColumn["auto_increment"]
      );

      $columns[] = $column;
    }

    $table->setColumns($columns);
    $created = $this->tableDAO->create($table);

    // произошла ошибка при добавлении таблицы
    if ($created === null) {
      $this->sendOutput(
        json_encode([
          "error" => "Ошибка добавления таблицы!"
        ], JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 500 Internal Server Error"]
      );
      return;
    }

    // добавление таблицы прошло успешно
    $this->sendOutput(
      json_encode($created->toArray(), JSON_UNESCAPED_UNICODE),
      ["Content-Type: application/json", "HTTP/1.1 201 Created"]
    );
  }

  /**
   * DELETE /api/tables/delete: удаляет таблицу из БД
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

    // id таблицы обязательно должен быть задан
    if (empty($params["id"])) {
      $this->sendOutput(
        json_encode([
          "error" => "Невозможно удалить пользователя! Не задан id клиента!"
        ], JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 400 Bad Request"]
      );
      return;
    }

    // ошибка при удалении пользователя
    if (!$this->tableDAO->delete($params["id"])) {
      $this->sendOutput(
        json_encode([
          "error" => "Произошла ошибка при удалении пользователя!"
        ], JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 500 Internal Server Error"]
      );
      return;
    }

    // успешное удаление
    $this->sendOutput(
      json_encode([
        "message" => "Удаление таблицы прошло успешно!"
      ], JSON_UNESCAPED_UNICODE),
      ["Content-Type: application/json", "HTTP/1.1 200 OK"]
    );
  }
}