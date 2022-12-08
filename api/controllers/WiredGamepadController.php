<?php

namespace Api\Controllers;

require_once __DIR__ . "/Controller.php";
require_once __DIR__ . "/../../config/Database.php";

require_once __DIR__ . "/../models/WiredGamepad.php";
require_once __DIR__ . "/../dao/WiredGamepadDAO.php";

use Api\Controllers\Controller;
use Config\Database;

use Api\Models\WiredGamepad;
use Api\Dao\WiredGamepadDAO;

class WiredGamepadController extends Controller {
  private WiredGamepadDAO $gamepadDAO;

  /**
   * Создаёт контроллер для работы с конечными точками /api/wiredgamepad
   */
  public function __construct() {
    $this->gamepadDAO = new WiredGamepadDAO((new Database())->getConnection());
  }

  /**
   * GET /api/wiredgamepad/list: возвращает список проводных геймпадов
   * GET /api/wiredgamepad/list?id=N: возвращает проводной геймпад с указанным ID
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

    // нужен только 1 проводной геймпад
    if (!empty($params["id"])) {
      $gamepad = $this->gamepadDAO->readOne($params["id"]);

      // проводной геймпад с заданным id не найден
      if ($gamepad === null) {
        $this->sendOutput(
          json_encode([
            "error" => "Проводной геймпад с id {$params['id']} не был найден!",
          ], JSON_UNESCAPED_UNICODE),
          ["Content-Type: application/json", "HTTP/1.1 400 Bad Request"]
        );
        return;
      }

      // проводной геймпад с заданным id найден
      $this->sendOutput(
        json_encode($gamepad->toArray(), JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 200 OK"]
      );
      return;
    }

    // конвертируем все объекты в ассоциативные массивы
    $gamepads = array_map(function (WiredGamepad $gamepad) {
      return $gamepad->toArray();
    }, $this->gamepadDAO->readAll());

    // конвертируем массивы в JSON-строки и отправляем ответ
    $this->sendOutput(
      json_encode($gamepads, JSON_UNESCAPED_UNICODE),
      ["Content-Type: application/json", "HTTP/1.1 200 OK"]
    );
  }

  /**
   * POST /api/wiredgamepad/create: создаёт проводной геймпад и добавляет его в БД
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
      || empty($data["cabel_length"])
      || empty($data["consumption"])
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
    $gamepad = new WiredGamepad();

    $gamepad->setName($data["name"]);
    $gamepad->setBrand($data["brand"]);
    $gamepad->setButtons($data["buttons"]);
    $gamepad->setPrice($data["price"]);
    $gamepad->setCabelLength($data["cabel_length"]);
    $gamepad->setConsumption($data["consumption"]);

    $created = $this->gamepadDAO->create($gamepad);

    // произошла ошибка при добавлении проводного геймпада
    if ($created === null) {
      $this->sendOutput(
        json_encode([
          "error" => "Ошибка добавления проводного геймпада!"
        ], JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 500 Internal Server Error"]
      );
      return;
    }

    // добавление проводного геймпада прошло успешно
    $this->sendOutput(
      json_encode($created->toArray(), JSON_UNESCAPED_UNICODE),
      ["Content-Type: application/json", "HTTP/1.1 201 Created"]
    );
  }

  /**
   * PUT /api/wiredgamepad/update: обновляет данные проводного геймпада в БД
   * @return void
   */
  public function updateAction(): void {
    $method = strtoupper($_SERVER["REQUEST_METHOD"]);

    // для данной конечной точки можно применить только метод PUT
    if ($method !== "PUT") {
      $this->sendOutput(
        json_encode([
          "error" => "Ошибка обновления данных проводного геймпада!"
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
      || empty($data["cabel_length"])
      || empty($data["consumption"])
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
    $gamepad = new WiredGamepad(
      $data["gamepad_id"],
      $data["name"],
      $data["brand"],
      $data["buttons"],
      $data["price"],
      $data["cabel_length"],
      $data["consumption"]
    );

    $updated = $this->gamepadDAO->update($gamepad);

    // произошла ошибка при обновлении данных проводного консоли
    if ($updated === null) {
      $this->sendOutput(
        json_encode([
          "error" => "Ошибка обновления данных проводной консоли!"
        ], JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 500 Internal Server Error"]
      );
      return;
    }

    // обновление данных проводного геймпада прошло успешно
    $this->sendOutput(
      json_encode($updated->toArray(), JSON_UNESCAPED_UNICODE),
      ["Content-Type: application/json", "HTTP/1.1 201 Created"]
    );
  }

  /**
   * DELETE /api/wiredgamepad/delete: удаляет проводной геймпад из БД
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

    // id проводного геймпада обязательно должен быть задан
    if (empty($params["id"])) {
      $this->sendOutput(
        json_encode([
          "error" => "Невозможно удалить проводной геймпад! Не задан id проводного геймпада!"
        ], JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 400 Bad Request"]
      );
      return;
    }

    // ошибка при удалении проводного геймпада
    if (!$this->gamepadDAO->delete($params["id"])) {
      $this->sendOutput(
        json_encode([
          "error" => "Произошла ошибка при удалении проводного геймпада!"
        ], JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 500 Internal Server Error"]
      );
      return;
    }

    // успешное удаление
    $this->sendOutput(
      json_encode([
        "message" => "Удаление проводного геймпада прошло успешно!"
      ], JSON_UNESCAPED_UNICODE),
      ["Content-Type: application/json", "HTTP/1.1 200 OK"]
    );
  }
}