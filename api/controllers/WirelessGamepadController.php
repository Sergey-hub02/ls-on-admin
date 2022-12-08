<?php

namespace Api\Controllers;

require_once __DIR__ . "/Controller.php";
require_once __DIR__ . "/../../config/Database.php";

require_once __DIR__ . "/../models/WirelessGamepad.php";
require_once __DIR__ . "/../dao/WirelessGamepadDAO.php";

use Api\Controllers\Controller;
use Config\Database;

use Api\Models\WirelessGamepad;
use Api\Dao\WirelessGamepadDAO;

class WirelessGamepadController extends Controller {
  private WirelessGamepadDAO $gamepadDAO;

  /**
   * Создаёт контроллер для работы с конечными точками /api/wirelessgamepad
   */
  public function __construct() {
    $this->gamepadDAO = new WirelessGamepadDAO((new Database())->getConnection());
  }

  /**
   * GET /api/wirelessgamepad/list: возвращает список беспроводных геймпадов
   * GET /api/wirelessgamepad/list?id=N: возвращает беспроводной геймпад с указанным ID
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

    // нужен только 1 беспроводной геймпад
    if (!empty($params["id"])) {
      $gamepad = $this->gamepadDAO->readOne($params["id"]);

      // беспроводной геймпад с заданным id не найден
      if ($gamepad === null) {
        $this->sendOutput(
          json_encode([
            "error" => "Беспроводной геймпад с id {$params['id']} не был найден!",
          ], JSON_UNESCAPED_UNICODE),
          ["Content-Type: application/json", "HTTP/1.1 400 Bad Request"]
        );
        return;
      }

      // беспроводной геймпад с заданным id найден
      $this->sendOutput(
        json_encode($gamepad->toArray(), JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 200 OK"]
      );
      return;
    }

    // конвертируем все объекты в ассоциативные массивы
    $gamepads = array_map(function (WirelessGamepad $gamepad) {
      return $gamepad->toArray();
    }, $this->gamepadDAO->readAll());

    // конвертируем массивы в JSON-строки и отправляем ответ
    $this->sendOutput(
      json_encode($gamepads, JSON_UNESCAPED_UNICODE),
      ["Content-Type: application/json", "HTTP/1.1 200 OK"]
    );
  }

  /**
   * POST /api/wirelessgamepad/create: создаёт беспроводной геймпад и добавляет его в БД
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
      || empty($data["brand"])
      || empty($data["buttons"])
      || empty($data["price"])
      || empty($data["capacity"])
      || empty($data["frequency"])
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
    $gamepad = new WirelessGamepad();

    $gamepad->setName($data["name"]);
    $gamepad->setBrand($data["brand"]);
    $gamepad->setButtons($data["buttons"]);
    $gamepad->setPrice($data["price"]);
    $gamepad->setCapacity($data["capacity"]);
    $gamepad->setFrequency($data["frequency"]);

    $created = $this->gamepadDAO->create($gamepad);

    // произошла ошибка при добавлении беспроводного геймпада
    if ($created === null) {
      $this->sendOutput(
        json_encode([
          "error" => "Ошибка добавления беспроводного геймпада!"
        ], JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 500 Internal Server Error"]
      );
      return;
    }

    // добавление беспроводного геймпада прошло успешно
    $this->sendOutput(
      json_encode($created->toArray(), JSON_UNESCAPED_UNICODE),
      ["Content-Type: application/json", "HTTP/1.1 201 Created"]
    );
  }

  /**
   * PUT /api/wirelessgamepad/update: обновляет данные беспроводного геймпада в БД
   * @return void
   */
  public function updateAction(): void {
    $method = strtoupper($_SERVER["REQUEST_METHOD"]);

    // для данной конечной точки можно применить только метод PUT
    if ($method !== "PUT") {
      $this->sendOutput(
        json_encode([
          "error" => "Ошибка обновления данных беспроводного геймпада!"
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
      empty($data["gamepad_id"])
      || empty($data["name"])
      || empty($data["brand"])
      || empty($data["buttons"])
      || empty($data["price"])
      || empty($data["capacity"])
      || empty($data["frequency"])
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
    $gamepad = new WirelessGamepad(
      $data["gamepad_id"],
      $data["name"],
      $data["brand"],
      $data["buttons"],
      $data["price"],
      $data["capacity"],
      $data["frequency"]
    );

    $updated = $this->gamepadDAO->update($gamepad);

    // произошла ошибка при обновлении данных беспроводного консоли
    if ($updated === null) {
      $this->sendOutput(
        json_encode([
          "error" => "Ошибка обновления данных беспроводной консоли!"
        ], JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 500 Internal Server Error"]
      );
      return;
    }

    // обновление данных беспроводного геймпада прошло успешно
    $this->sendOutput(
      json_encode($updated->toArray(), JSON_UNESCAPED_UNICODE),
      ["Content-Type: application/json", "HTTP/1.1 201 Created"]
    );
  }

  /**
   * DELETE /api/wirelessgamepad/delete: удаляет беспроводной геймпад из БД
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

    // id беспроводного геймпада обязательно должен быть задан
    if (empty($params["id"])) {
      $this->sendOutput(
        json_encode([
          "error" => "Невозможно удалить проводной геймпад! Не задан id проводного геймпада!"
        ], JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 400 Bad Request"]
      );
      return;
    }

    // ошибка при удалении беспроводного геймпада
    if (!$this->gamepadDAO->delete($params["id"])) {
      $this->sendOutput(
        json_encode([
          "error" => "Произошла ошибка при удалении беспроводного геймпада!"
        ], JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 500 Internal Server Error"]
      );
      return;
    }

    // успешное удаление
    $this->sendOutput(
      json_encode([
        "message" => "Удаление беспроводного геймпада прошло успешно!"
      ], JSON_UNESCAPED_UNICODE),
      ["Content-Type: application/json", "HTTP/1.1 200 OK"]
    );
  }
}