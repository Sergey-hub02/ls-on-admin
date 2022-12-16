<?php

namespace Scripts;

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");


/**
 * @param string $yesOrNo
 * @return bool
 */
function YNtoBool(string $yesOrNo): bool {
  return $yesOrNo === "Y";
}


if (isset($_REQUEST["create-table"])) {
  $tableName = trim($_REQUEST["table-name"]);
  $databaseId = intval($_REQUEST["database_id"]);

  $columns = [];
  $columnsLength = count($_REQUEST["name"]);

  for ($i = 0; $i < $columnsLength; ++$i) {
    $columns[] = [
      "name" => $_REQUEST["name"][$i],
      "type" => $_REQUEST["type"][$i],
      "null" => YNtoBool($_REQUEST["null"][$i]),
      "primary" => YNtoBool($_REQUEST["primary"][$i]),
      "auto_increment" => YNtoBool($_REQUEST["auto_increment"][$i])
    ];
  }

  $data = json_encode([
    "name" => $tableName,
    "database_id" => $databaseId,
    "columns" => $columns,
  ], JSON_UNESCAPED_UNICODE);

  $ch = curl_init();

  curl_setopt($ch, CURLOPT_URL, "http://localhost/api/index.php/tables/create");
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

  $result = curl_exec($ch);
  if ($result === false) {
    http_response_code(500);

    echo json_encode([
      "errors" => ["Ошибка при добавлении таблицы!"],
    ], JSON_UNESCAPED_UNICODE);

    die();
  }

  http_response_code(201);
  echo $result;
}
