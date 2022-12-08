<?php

namespace Scripts;

require_once __DIR__ . "/../config/Connection.php";

use PDO;
use Config\Connection;

if (isset($_REQUEST["login"])) {
  $username = trim($_REQUEST["username"]);
  $password = trim($_REQUEST["password"]);

  /* ВАЛИДАЦИЯ ДАННЫХ */
  $errors[] = [];

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
  $query = "SELECT `password` FROM `User` WHERE `username` = :username";

  $stmt = $conn->prepare($query);
  $stmt->bindParam(":username", $username);

  if (!$stmt->execute()) {
    http_response_code(500);

    echo json_encode([
      "errors" => ["Ошибка при попытке запросить пользовательские данные!"],
    ], JSON_UNESCAPED_UNICODE);

    die();
  }

  $result = $stmt->fetchAll(PDO::FETCH_ASSOC)[0];
  if (!password_verify($password, $result["password"])) {
    http_response_code(400);

    echo json_encode([
      "errors" => ["Неверный логин или пароль!"],
    ], JSON_UNESCAPED_UNICODE);

    die();
  }

  http_response_code(200);

  echo json_encode([
    "message" => "Вы успешно авторизовались!",
  ], JSON_UNESCAPED_UNICODE);
}
