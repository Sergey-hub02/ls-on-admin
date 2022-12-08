<?php

namespace Api\Controllers;

require_once __DIR__ . "/Controller.php";
require_once __DIR__ . "/../../config/Database.php";

require_once __DIR__ . "/../models/Order.php";
require_once __DIR__ . "/../models/Client.php";
require_once __DIR__ . "/../models/Console.php";
require_once __DIR__ . "/../models/Gamepad.php";
require_once __DIR__ . "/../models/OrderConsole.php";
require_once __DIR__ . "/../models/OrderGamepad.php";

require_once __DIR__ . "/../dao/OrderDAO.php";

use Config\Database;
use Api\Models\Order;
use Api\Models\User;
use Api\Models\Console;
use Api\Models\Gamepad;
use Api\Models\OrderConsole;
use Api\Models\OrderGamepad;
use Api\Dao\OrderDAO;

class OrderController extends Controller {
  private OrderDAO $orderDAO;

  /**
   * Создаёт контроллер для работы с конечными точками /api/orders
   */
  public function __construct() {
    $this->orderDAO = new OrderDAO((new Database())->getConnection());
  }

  /**
   * GET /api/orders/list: возвращает список заказов
   * GET /api/orders/list?id=N: возвращает заказ с id = N
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

    // нужен только один заказ
    if (!empty($params["id"])) {
      $order = $this->orderDAO->readOne($params["id"]);

      // заказ с заданным id не найден
      if ($order === null) {
        $this->sendOutput(
          json_encode([
            "error" => "Заказ с id {$params['id']} не был найден!",
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
    $orders = array_map(function (Order $order) {
      return $order->toArray();
    }, $this->orderDAO->readAll());

    // конвертируем массивы в JSON-строки и отправляем ответ
    $this->sendOutput(
      json_encode($orders, JSON_UNESCAPED_UNICODE),
      ["Content-Type: application/json", "HTTP/1.1 200 OK"]
    );
  }

  /**
   * POST /api/orders/create: создаёт заказ и сохраняет его в БД
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
      empty($data["client"]["client_id"])
      || empty($data["ordersconsole"])
      || empty($data["ordersgamepad"])
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
    $client = new User();
    $client->setClientId($data["client"]["client_id"]);

    $ordersConsole = [];
    foreach ($data["ordersconsole"] as $arOrderConsole) {
      $console = new Console();
      $console->setConsoleId(intval($arOrderConsole["console"]["console_id"]));

      $orderConsole = new OrderConsole($console, intval($arOrderConsole["amount"]));
      $ordersConsole[] = $orderConsole;
    }

    $ordersGamepad = [];
    foreach ($data["ordersgamepad"] as $arOrderGamepad) {
      $gamepad = new Gamepad();
      $gamepad->setGamepadId(intval($arOrderGamepad["gamepad"]["gamepad_id"]));

      $orderGamepad = new OrderGamepad($gamepad, intval($arOrderGamepad["amount"]));
      $ordersGamepad[] = $orderGamepad;
    }

    $order = new Order();
    $order->setClient($client);
    $order->setOrdersConsole($ordersConsole);
    $order->setOrdersGamepad($ordersGamepad);
    $order->setPrice(floatval($data["price"]));

    $created = $this->orderDAO->create($order);

    // произошла ошибка при добавлении заказа
    if ($created === null) {
      $this->sendOutput(
        json_encode([
          "error" => "Ошибка добавления заказа!"
        ], JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 500 Internal Server Error"]
      );
      return;
    }

    // добавление заказа прошло успешно
    $this->sendOutput(
      json_encode($created->toArray(), JSON_UNESCAPED_UNICODE),
      ["Content-Type: application/json", "HTTP/1.1 201 Created"]
    );
  }

  /**
   * DELETE /api/orders/delete: удаляет заказ из БД
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

    // id заказа обязательно должен быть задан
    if (empty($params["id"])) {
      $this->sendOutput(
        json_encode([
          "error" => "Невозможно удалить заказ! Не задан id заказа!"
        ], JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 400 Bad Request"]
      );
      return;
    }

    // ошибка при удалении заказа
    if (!$this->orderDAO->delete($params["id"])) {
      $this->sendOutput(
        json_encode([
          "error" => "Произошла ошибка при удалении заказа!"
        ], JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 500 Internal Server Error"]
      );
      return;
    }

    // успешное удаление
    $this->sendOutput(
      json_encode([
        "message" => "Удаление заказа прошло успешно!"
      ], JSON_UNESCAPED_UNICODE),
      ["Content-Type: application/json", "HTTP/1.1 200 OK"]
    );
  }
}
