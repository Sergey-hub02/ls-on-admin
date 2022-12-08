<?php

namespace Api\Controllers;

require_once __DIR__ . "/Controller.php";
require_once __DIR__ . "/../../config/Database.php";

require_once __DIR__ . "/../models/Console.php";
require_once __DIR__ . "/../dao/ConsoleDAO.php";

use Api\Controllers\Controller;
use Config\Database;

use Api\Models\Console;
use Api\Dao\ConsoleDAO;

class ConsoleController extends Controller {
  private ConsoleDAO $consoleDAO;

  /**
   * Создаёт контроллер для работы с конечными точками /api/console
   */
  public function __construct() {
    $this->consoleDAO = new ConsoleDAO((new Database())->getConnection());
  }

  /**
   * GET /api/console/list: возвращает список консолей
   * GET /api/console/list?id=N: возвращает консоль с указанным ID
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

    // нужна только 1 консоль
    if (!empty($params["id"])) {
      $console = $this->consoleDAO->readOne($params["id"]);

      // клиент с заданным id не найден
      if ($console === null) {
        $this->sendOutput(
          json_encode([
            "error" => "Консоль с id {$params['id']} не был найден!",
          ], JSON_UNESCAPED_UNICODE),
          ["Content-Type: application/json", "HTTP/1.1 400 Bad Request"]
        );
        return;
      }

      // клиент с заданным id найден
      $this->sendOutput(
        json_encode($console->toArray(), JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 200 OK"]
      );
      return;
    }

    // конвертируем все объекты в ассоциативные массивы
    $consoles = array_map(function (Console $console) {
      return $console->toArray();
    }, $this->consoleDAO->readAll());

    // конвертируем массивы в JSON-строки и отправляем ответ
    $this->sendOutput(
      json_encode($consoles, JSON_UNESCAPED_UNICODE),
      ["Content-Type: application/json", "HTTP/1.1 200 OK"]
    );
  }

  /**
   * POST /api/console/create: создаёт клиента и добавляет его в БД
   * @return void
   */
  public function createAction(): void {
    // TODO: добавить проверку на наличие файла для создания записи
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
      || empty($data["brand"])
      || empty($data["gpu"])
      || empty($data["cpu"])
      || empty($data["ram"])
      || empty($data["price"])
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
    $console = new Console();

    $console->setName($data["name"]);
    $console->setBrand($data["brand"]);
    $console->setGpu($data["gpu"]);
    $console->setCpu($data["cpu"]);
    $console->setRam($data["ram"]);
    $console->setPrice($data["price"]);

    $created = $this->consoleDAO->create($console);

    // произошла ошибка при добавлении консоли
    if ($created === null) {
      $this->sendOutput(
        json_encode([
          "error" => "Ошибка добавления консоли!"
        ], JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 500 Internal Server Error"]
      );
      return;
    }

    // добавление клиента прошло успешно
    $this->sendOutput(
      json_encode($created->toArray(), JSON_UNESCAPED_UNICODE),
      ["Content-Type: application/json", "HTTP/1.1 201 Created"]
    );
  }

  /**
   * PUT /api/console/update: обновляет данные консоли в БД
   * @return void
   */
  public function updateAction(): void {
    $method = strtoupper($_SERVER["REQUEST_METHOD"]);

    // для данной конечной точки можно применить только метод PUT
    if ($method !== "PUT") {
      $this->sendOutput(
        json_encode([
          "error" => "Ошибка обновления данных консоли!"
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

    // проверка на полноценность данных
    if (
      empty($data["console_id"])
      || empty($data["name"])
      || empty($data["brand"])
      || empty($data["gpu"])
      || empty($data["cpu"])
      || empty($data["ram"])
      || empty($data["price"])
    ) {
      $this->sendOutput(
        json_encode([
          "error" => "Невозможно выполнить запрос! Неполные данные!"
        ], JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 400 Bad Request"]
      );
      return;
    }

    // все данные для обновления присутствуют
    $console = new Console(
      $data["console_id"],
      $data["name"],
      $data["brand"],
      $data["gpu"],
      $data["cpu"],
      $data["ram"],
      $data["price"]
    );

    $updated = $this->consoleDAO->update($console);

    // произошла ошибка при обновлении данных консоли
    if ($updated === null) {
      $this->sendOutput(
        json_encode([
          "error" => "Ошибка обновления данных консоли!"
        ], JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 500 Internal Server Error"]
      );
      return;
    }

    // обновление данных консоли прошло успешно
    $this->sendOutput(
      json_encode($updated->toArray(), JSON_UNESCAPED_UNICODE),
      ["Content-Type: application/json", "HTTP/1.1 201 Created"]
    );
  }

  /**
   * DELETE /api/console/delete: удаляет клиента из БД
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

    // id консоли обязательно должен быть задан
    if (empty($params["id"])) {
      $this->sendOutput(
        json_encode([
          "error" => "Невозможно удалить консоль! Не задан id консоли!"
        ], JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 400 Bad Request"]
      );
      return;
    }

    // ошибка при удалении консоли
    if (!$this->consoleDAO->delete($params["id"])) {
      $this->sendOutput(
        json_encode([
          "error" => "Произошла ошибка при удалении консоли!"
        ], JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 500 Internal Server Error"]
      );
      return;
    }

    // успешное удаление
    $this->sendOutput(
      json_encode([
        "message" => "Удаление консоли прошло успешно!"
      ], JSON_UNESCAPED_UNICODE),
      ["Content-Type: application/json", "HTTP/1.1 200 OK"]
    );
  }
}