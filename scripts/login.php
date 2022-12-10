<?php

namespace Scripts;

session_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/../config/Connection.php";

use PDO;
use Config\Connection;

if (isset($_REQUEST["login"])) {
  $username = trim($_REQUEST["username"]);
  $password = trim($_REQUEST["password"]);

  /* ВАЛИДАЦИЯ ДАННЫХ */
  $errors = [];

  if (empty($username)) {
    $errors[] = "Пожалуйста, заполните поле \"Логин\"!";
  }

  if (empty($password)) {
    $errors[] = "Пожалуйста, заполните поле \"Пароль\"!";
  }

  // если есть ошибки, то выводим их в ответе
  if (!empty($errors)) {
    http_response_code(400);

    echo json_encode([
      "errors" => $errors,
    ], JSON_UNESCAPED_UNICODE);

    die();
  }

  /* ПРОВЕДЕНИЕ АВТОРИЗАЦИИ */
  $conn = (new Connection())->getConnection();
  $query = "SELECT * FROM `User` WHERE `username` = ?";

  $stmt = $conn->prepare($query);

  if (!$stmt->execute([$username])) {
    http_response_code(500);

    echo json_encode([
      "errors" => ["Ошибка при попытке запросить пользовательские данные!"],
    ], JSON_UNESCAPED_UNICODE);

    die();
  }

  $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

  if (empty($result)) {
    http_response_code(400);

    echo json_encode([
      "errors" => ["Нет такого логина!"],
    ], JSON_UNESCAPED_UNICODE);

    die();
  }

  $result = $result[0];

  if (!password_verify($password, $result["password"])) {
    http_response_code(400);

    echo json_encode([
      "errors" => ["Неверный пароль!"],
    ], JSON_UNESCAPED_UNICODE);

    die();
  }

  http_response_code(200);

  echo json_encode([
    "message" => "Вы успешно авторизовались!",
  ], JSON_UNESCAPED_UNICODE);

  // сохраняем данные пользователя для сессии
  $_SESSION["user_id"] = $result["user_id"];
  $_SESSION["username"] = $result["username"];
  $_SESSION["email"] = $result["email"];
  $_SESSION["role_id"] = $result["role_id"];
}
