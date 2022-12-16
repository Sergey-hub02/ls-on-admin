<?php

namespace Scripts;

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/../config/Connection.php";
require_once __DIR__ . "/../config/DBConnection.php";

use PDO;
use Config\Connection;
use Config\DBConnection;

if (isset($_REQUEST["remove-table"])) {
  $tableId = intval($_REQUEST["table_id"]);
  $databaseName = trim($_REQUEST["database-name"]);
  $tableName = trim($_REQUEST["name"]);

  $conn = (new Connection())->getConnection();

  $query = "DELETE FROM `Column` WHERE `table_id` = :tableid";
  $stmt = $conn->prepare($query);

  $stmt->bindParam(":tableid", $tableId);
  if (!$stmt->execute()) {
    http_response_code(500);

    echo json_encode([
      "error" => "Ошибка при удалении таблицы!",
    ], JSON_UNESCAPED_UNICODE);

    die();
  }

  $query = "DELETE FROM `Table` WHERE `table_id` = ?";
  $stmt = $conn->prepare($query);

  if (!$stmt->execute([$tableId])) {
    http_response_code(500);

    echo json_encode([
      "error" => "Ошибка при удалении таблицы!",
    ], JSON_UNESCAPED_UNICODE);

    die();
  }

//  $dbConn = (new DBConnection())->getConnection();

  $query = "DROP TABLE `$databaseName`.`$tableName`";
  $conn->query($query);

  http_response_code(200);
  echo json_encode([
    "message" => "Удаление таблицы прошло успешно!",
  ], JSON_UNESCAPED_UNICODE);
}
