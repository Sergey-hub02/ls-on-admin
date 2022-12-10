<?php

namespace Scripts;
session_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/../config/Connection.php";

use PDO;
use Config\Connection;

/**
 * Проверяет, является ли строка целым числом
 * @param string $value       проверяемая строка
 * @return bool
 */
function isInteger(string $value): bool {
  return (
    is_numeric($value)
    && !str_contains($value, ".")
    && !str_contains($value, ",")
    && intval($value) >= 1
  );
}


if (isset($_REQUEST["register"])) {
  $username = trim($_REQUEST["username"]);
  $email = trim($_REQUEST["email"]);
  $password = trim($_REQUEST["password"]);
  $role_id = $_REQUEST["role_id"];

  /* ВАЛИДАЦИЯ ДАННЫХ */
  $errors = [];

  if (empty($username)) {
    $errors[] = "Пожалуйста, заполните поле \"Логин\"!";
  }

  if (empty($email)) {
    $errors[] = "Пожалуйста, заполните поле \"Email\"!";
  }

  if (empty($password)) {
    $errors[] = "Пожалуйста, заполните поле \"Пароль\"!";
  }

  if (empty($role_id) || !isInteger($role_id)) {
    $errors[] = "Невозможно определить роль пользователя!";
  }

  // если есть ошибки, передаём их на клиент
  if (!empty($errors)) {
    http_response_code(400);

    echo json_encode([
      "errors" => $errors,
    ], JSON_UNESCAPED_UNICODE);

    die();
  }

  /* ПРОВЕДЕНИЕ РЕГИСТРАЦИИ */
  $role_id = intval($role_id);

  $ch = curl_init();

  curl_setopt($ch, CURLOPT_URL, "http://localhost/api/index.php/users/create");
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    "username" => $username,
    "email" => $email,
    "password" => $password,
    "role_id" => $role_id
  ], JSON_UNESCAPED_UNICODE));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

  $result = curl_exec($ch);

  if ($result === false) {
    http_response_code(500);

    echo json_encode([
      "errors" => ["Ошибка при выполнении запроса!"],
    ], JSON_UNESCAPED_UNICODE);

    die();
  }

  http_response_code(201);
  echo $result;

  $result = json_decode($result, true);

  // сохраняем данные пользователя для сессии
  $_SESSION["user_id"] = $result["user_id"];
  $_SESSION["username"] = $result["username"];
  $_SESSION["email"] = $result["email"];
}
