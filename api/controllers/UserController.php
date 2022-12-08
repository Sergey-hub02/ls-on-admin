<?php

namespace Api\Controllers;

require_once __DIR__ . "/Controller.php";
require_once __DIR__ . "/../../config/Connection.php";

require_once __DIR__ . "/../models/User.php";
require_once __DIR__ . "/../models/Role.php";

require_once __DIR__ . "/../dao/UserDAO.php";

use Config\Connection;
use Api\Models\User;
use Api\Models\Role;
use Api\Dao\UserDAO;

class UserController extends Controller {
  private UserDAO $userDAO;

  /**
   * Создаёт контроллер для работы с конечными точками /api/users
   */
  public function __construct() {
    $this->userDAO = new UserDAO((new Connection())->getConnection());
  }

  /**
   * GET /api/users/list: возвращает список пользователей
   * GET /api/users/list?id=N: возвращает пользователя с указанным ID
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

    // нужен только один пользователь
    if (!empty($params["id"])) {
      $user = $this->userDAO->readOne($params["id"]);

      // пользователь с заданным id не найден
      if ($user === null) {
        $this->sendOutput(
          json_encode([
            "error" => "Пользователь с id {$params['id']} не был найден!",
          ], JSON_UNESCAPED_UNICODE),
          ["Content-Type: application/json", "HTTP/1.1 400 Bad Request"]
        );
        return;
      }

      // пользователь с заданным id найден
      $this->sendOutput(
        json_encode($user->toArray(), JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 200 OK"]
      );
      return;
    }

    // конвертируем все объекты в ассоциативные массивы
    $users = array_map(function (User $user) {
      return $user->toArray();
    }, $this->userDAO->readAll());

    // конвертируем массивы в JSON-строки и отправляем ответ
    $this->sendOutput(
      json_encode($users, JSON_UNESCAPED_UNICODE),
      ["Content-Type: application/json", "HTTP/1.1 200 OK"]
    );
  }

  /**
   * POST /api/users/create: создаёт пользователя и добавляет его в БД
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
      empty($data["username"])
      || empty($data["email"])
      || empty($data["password"])
      || empty($data["role_id"])
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
    $user = new User();

    $user->setUsername($data["username"]);
    $user->setEmail($data["email"]);
    $user->setPassword(
      password_hash($data["password"], PASSWORD_DEFAULT)
    );

    $user->setRole(new Role($data["role_id"]));

    $created = $this->userDAO->create($user);

    // произошла ошибка при добавлении пользователя
    if ($created === null) {
      $this->sendOutput(
        json_encode([
          "error" => "Ошибка добавления пользователя!"
        ], JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 500 Internal Server Error"]
      );
      return;
    }

    // добавление пользователя прошло успешно
    $this->sendOutput(
      json_encode($created->toArray(), JSON_UNESCAPED_UNICODE),
      ["Content-Type: application/json", "HTTP/1.1 201 Created"]
    );
  }

  /**
   * PUT /api/users/update: обновляет данные пользователя в БД
   * @return void
   */
  public function updateAction(): void {
    $method = strtoupper($_SERVER["REQUEST_METHOD"]);

    // для данной конечной точки можно применить только метод PUT
    if ($method !== "PUT") {
      $this->sendOutput(
        json_encode([
          "error" => "Метод $method не поддерживается"
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
      empty($data["user_id"])
      || empty($data["username"])
      || empty($data["email"])
      || empty($data["password"])
      || empty($data["role_id"])
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
    $user = new User(
      $data["user_id"],
      $data["username"],
      $data["email"],
      password_hash($data["password"], PASSWORD_DEFAULT),
      new Role($data["role_id"])
    );

    $updated = $this->userDAO->update($user);

    // произошла ошибка при обновлении данных пользователя
    if ($updated === null) {
      $this->sendOutput(
        json_encode([
          "error" => "Ошибка обновления данных пользователя!"
        ], JSON_UNESCAPED_UNICODE),
        ["Content-Type: application/json", "HTTP/1.1 500 Internal Server Error"]
      );
      return;
    }

    // обновление данных клиента прошло успешно
    $this->sendOutput(
      json_encode($updated->toArray(), JSON_UNESCAPED_UNICODE),
      ["Content-Type: application/json", "HTTP/1.1 201 Created"]
    );
  }

  /**
   * DELETE /api/users/delete: удаляет пользователя из БД
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

    // id пользователя обязательно должен быть задан
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
    if (!$this->userDAO->delete($params["id"])) {
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
        "message" => "Удаление пользователя прошло успешно!"
      ], JSON_UNESCAPED_UNICODE),
      ["Content-Type: application/json", "HTTP/1.1 200 OK"]
    );
  }
}
