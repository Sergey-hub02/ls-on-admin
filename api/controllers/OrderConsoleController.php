<?php

namespace Api\Controllers;

require_once __DIR__ . "/Controller.php";
require_once __DIR__ . "/../../config/Database.php";

require_once __DIR__ . "/../models/Console.php";
require_once __DIR__ . "/../models/Client.php";
require_once __DIR__ . "/../models/OrderConsole.php";
require_once __DIR__ . "/../dao/OrderConsoleDAO.php";

use Api\Controllers\Controller;
use Config\Database;

use Api\Models\Console;
use Api\Models\User;
use Api\Models\OrderConsole;
use Api\Dao\OrderConsoleDAO;

class OrderConsoleController extends Controller {
  private OrderConsoleDAO $orderConsoleDAO;

  /**
   * Создаёт контроллер для работы с конечными точками /api/orderconsole
   */
  public function __construct() {
    $this->orderConsoleDAO = new OrderConsoleDAO((new Database())->getConnection());
  }

  /**
   * GET /api/orderconsole/list: возвращает список заказов консолей
   * GET /api/orderconsole/list?id=N: возвращает заказ с указанным ID
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

    // нужен только 1 заказ
    if (!empty($params["id"])) {
      $order = $this->orderConsoleDAO->readOne($params["id"]);

      // заказ с заданным id не найден
      if ($order === null) {
        $this->sendOutput(
          json_encode([
            "error" => "Заказ консоли с id {$params['id']} не был найден!",
          ], JSON_UNESCAPED_UNICODE),
          ["Content-Type: application/json", "HTTP/1.1 400 Bad Request"]
        );
        return;
      }

      // заказ с заданным id найден
      $this->sendOutput(
        json_encode($order->toArray(), JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 200 OK"]
      );
      return;
    }

    // конвертируем все объекты в ассоциативные массивы
    $orders = array_map(function (OrderConsole $order) {
      return $order->toArray();
    }, $this->orderConsoleDAO->readAll());

    // конвертируем массивы в JSON-строки и отправляем ответ
    $this->sendOutput(
      json_encode($orders, JSON_UNESCAPED_UNICODE),
      ["Content-Type: application/json", "HTTP/1.1 200 OK"]
    );
  }

  /**
   * POST /api/orderconsole/create: создаёт заказ консоли и добавляет его в БД
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
      empty($data["console_id"])
      || empty($data["client_id"])
      || empty($data["amount"])
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
    $console->setConsoleId($data["console_id"]);

    $client = new User();
    $client->setClientId($data["client_id"]);

    $order = new OrderConsole();

    $order->setConsole($console);
    $order->setClient($client);
    $order->setAmount($data["amount"]);

    $created = $this->orderConsoleDAO->create($order);

    // произошла ошибка при добавлении заказа консоли
    if ($created === null) {
      $this->sendOutput(
        json_encode([
          "error" => "Ошибка добавления заказа консоли!"
        ], JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 500 Internal Server Error"]
      );
      return;
    }

    // добавление консоли прошло успешно
    $this->sendOutput(
      json_encode($created->toArray(), JSON_UNESCAPED_UNICODE),
      ["Content-Type: application/json", "HTTP/1.1 201 Created"]
    );
  }

  /**
   * PUT /api/orderconsole/update: обновляет данные заказа консоли в БД
   * @return void
   */
  public function updateAction(): void {
    $method = strtoupper($_SERVER["REQUEST_METHOD"]);

    // для данной конечной точки можно применить только метод PUT
    if ($method !== "PUT") {
      $this->sendOutput(
        json_encode([
          "error" => "Ошибка обновления данных заказа консоли!"
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
      empty($data["order_id"])
      || empty($data["console_id"])
      || empty($data["client_id"])
      || empty($data["amount"])
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
    $console = new Console($data["console_id"]);
    $client = new User($data["client_id"]);

    $order = new OrderConsole(
      $data["order_id"],
      $console,
      $client,
      $data["amount"]
    );

    $updated = $this->orderConsoleDAO->update($order);

    // произошла ошибка при обновлении данных заказа геймпада
    if ($updated === null) {
      $this->sendOutput(
        json_encode([
          "error" => "Ошибка обновления данных заказа консоли!"
        ], JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 500 Internal Server Error"]
      );
      return;
    }

    // обновление данных заказа консоли прошло успешно
    $this->sendOutput(
      json_encode($updated->toArray(), JSON_UNESCAPED_UNICODE),
      ["Content-Type: application/json", "HTTP/1.1 201 Created"]
    );
  }

  /**
   * DELETE /api/orderconsole/delete: удаляет заказ консоли из БД
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

    // id заказа консоли обязательно должен быть задан
    if (empty($params["id"])) {
      $this->sendOutput(
        json_encode([
          "error" => "Невозможно удалить заказ консоли! Не задан id консоли!"
        ], JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 400 Bad Request"]
      );
      return;
    }

    // ошибка при удалении заказа консоли
    if (!$this->orderConsoleDAO->delete($params["id"])) {
      $this->sendOutput(
        json_encode([
          "error" => "Произошла ошибка при удалении заказа консоли!"
        ], JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 500 Internal Server Error"]
      );
      return;
    }

    // успешное удаление
    $this->sendOutput(
      json_encode([
        "message" => "Удаление заказа консоли прошло успешно!"
      ], JSON_UNESCAPED_UNICODE),
      ["Content-Type: application/json", "HTTP/1.1 200 OK"]
    );
  }
}