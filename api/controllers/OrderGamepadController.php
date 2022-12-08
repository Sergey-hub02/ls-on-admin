<?php

namespace Api\Controllers;

require_once __DIR__ . "/Controller.php";
require_once __DIR__ . "/../../config/Database.php";

require_once __DIR__ . "/../models/Gamepad.php";
require_once __DIR__ . "/../models/Client.php";
require_once __DIR__ . "/../models/OrderGamepad.php";
require_once __DIR__ . "/../dao/OrderGamepadDAO.php";

use Api\Controllers\Controller;
use Config\Database;

use Api\Models\Gamepad;
use Api\Models\User;
use Api\Models\OrderGamepad;
use Api\Dao\OrderGamepadDAO;

class OrderGamepadController extends Controller {
  private OrderGamepadDAO $orderGamepadDAO;

  /**
   * Создаёт контроллер для работы с конечными точками /api/ordergamepad
   */
  public function __construct() {
    $this->orderGamepadDAO = new OrderGamepadDAO((new Database())->getConnection());
  }

  /**
   * GET /api/ordergamepad/list: возвращает список заказов геймпадов
   * GET /api/ordergamepad/list?id=N: возвращает заказ с указанным ID
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
      $order = $this->orderGamepadDAO->readOne($params["id"]);

      // заказ с заданным id не найден
      if ($order === null) {
        $this->sendOutput(
          json_encode([
            "error" => "Заказ геймпада с id {$params['id']} не был найден!",
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
    $orders = array_map(function (OrderGamepad $order) {
      return $order->toArray();
    }, $this->orderGamepadDAO->readAll());

    // конвертируем массивы в JSON-строки и отправляем ответ
    $this->sendOutput(
      json_encode($orders, JSON_UNESCAPED_UNICODE),
      ["Content-Type: application/json", "HTTP/1.1 200 OK"]
    );
  }

  /**
   * POST /api/ordergamepad/create: создаёт заказ геймпада и добавляет его в БД
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
      empty($data["gamepad_id"])
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
    $gamepad = new Gamepad();
    $gamepad->setGamepadId($data["gamepad_id"]);

    $client = new User();
    $client->setClientId($data["client_id"]);

    $order = new OrderGamepad();

    $order->setGamepad($gamepad);
    $order->setClient($client);
    $order->setAmount($data["amount"]);

    $created = $this->orderGamepadDAO->create($order);

    // произошла ошибка при добавлении заказа геймпада
    if ($created === null) {
      $this->sendOutput(
        json_encode([
          "error" => "Ошибка добавления заказа геймпада!"
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
   * PUT /api/ordergamepad/update: обновляет данные заказа геймпада в БД
   * @return void
   */
  public function updateAction(): void {
    $method = strtoupper($_SERVER["REQUEST_METHOD"]);

    // для данной конечной точки можно применить только метод PUT
    if ($method !== "PUT") {
      $this->sendOutput(
        json_encode([
          "error" => "Ошибка обновления данных заказа геймпада!"
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
      || empty($data["gamepad_id"])
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
    $gamepad = new Gamepad($data["gamepad_id"]);
    $client = new User($data["client_id"]);

    $order = new OrderGamepad(
      $data["order_id"],
      $gamepad,
      $client,
      $data["amount"]
    );

    $updated = $this->orderGamepadDAO->update($order);

    // произошла ошибка при обновлении данных заказа геймпада
    if ($updated === null) {
      $this->sendOutput(
        json_encode([
          "error" => "Ошибка обновления данных заказа геймпада!"
        ], JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 500 Internal Server Error"]
      );
      return;
    }

    // обновление данных заказа геймпада прошло успешно
    $this->sendOutput(
      json_encode($updated->toArray(), JSON_UNESCAPED_UNICODE),
      ["Content-Type: application/json", "HTTP/1.1 201 Created"]
    );
  }

  /**
   * DELETE /api/ordergamepad/delete: удаляет заказа геймпада из БД
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

    // id заказа геймпада обязательно должен быть задан
    if (empty($params["id"])) {
      $this->sendOutput(
        json_encode([
          "error" => "Невозможно удалить заказ геймпада! Не задан id заказа!"
        ], JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 400 Bad Request"]
      );
      return;
    }

    // ошибка при удалении заказа геймпада
    if (!$this->orderGamepadDAO->delete($params["id"])) {
      $this->sendOutput(
        json_encode([
          "error" => "Произошла ошибка при удалении заказа геймпада!"
        ], JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 500 Internal Server Error"]
      );
      return;
    }

    // успешное удаление
    $this->sendOutput(
      json_encode([
        "message" => "Удаление заказа геймпада прошло успешно!"
      ], JSON_UNESCAPED_UNICODE),
      ["Content-Type: application/json", "HTTP/1.1 200 OK"]
    );
  }
}