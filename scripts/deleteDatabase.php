<?php

namespace Scripts;

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/../config/Connection.php";
require_once __DIR__ . "/../config/DBConnection.php";
require_once __DIR__ . "/../api/dao/TableDAO.php";

use PDO;
use Config\Connection;
use Config\DBConnection;
use Api\Dao\TableDAO;

if (isset($_REQUEST["remove-db"])) {
  $columnsQuery = "DELETE FROM `Column` WHERE `table_id` = ?";
  $tablesQuery = "DELETE FROM `Table` WHERE `database_id` = ?";
  $databasesQuery = "DELETE FROM `Database` WHERE `database_id` = ?";

  $dbId = intval($_REQUEST["database_id"]);
  $databaseName = trim($_REQUEST["database-name"]);

  $conn = (new Connection())->getConnection();
  $dbConn = (new DBConnection())->getConnection();

  $dao = new TableDAO($conn, $dbConn);
  $tables = $dao->getDatabaseTables($dbId);

  foreach ($tables as $table) {
    $dao->delete($table->getTableId());
  }
}
