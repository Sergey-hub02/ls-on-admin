<?php

namespace Api\Dao;

require_once __DIR__ . "/../models/Table.php";
require_once __DIR__ . "/../models/Column.php";

use PDO;
use mysqli;
use Api\Models\Table;
use Api\Models\Column;

class TableDAO {
  private PDO $connection;
  private mysqli $dbConnection;

  /**
   * Проверяет, существует ли таблица с указанным ID
   * @param int $tableId
   * @return bool
   */
  private function exists(int $tableId): bool {
    $query = "SELECT * FROM `Table` WHERE `table_id` = ?";
    $stmt = $this->connection->prepare($query);

    if (!$stmt->execute([$tableId])) {
      return false;
    }

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return count($result) > 0;
  }

  /**
   * @param PDO $connection       подключение к БД
   */
  public function __construct(PDO $connection, mysqli $dbConnection) {
    $this->connection = $connection;
    $this->dbConnection = $dbConnection;
  }

  /**
   * Создаёт объект таблицы и добавляет его в БД
   * @param Table $table
   * @return Table|null
   */
  public function create(Table $table): ?Table {
    $this->connection->beginTransaction();

    $query = "
      INSERT INTO `Table`(
        `name`,
        `database_id`
      )
      VALUES
        (?, ?)
    ";

    $stmt = $this->connection->prepare($query);

    if (!$stmt->execute([
      $table->getName(),
      $table->getDatabaseId()
    ])) {
      $this->connection->rollBack();
      return null;
    }

    $table->setTableId($this->connection->lastInsertId());

    $query = "
    INSERT INTO `Column`(
     `name`,
     `type`,
     `null`,
     `primary`,
     `auto_increment`,
     `table_id`
    )
    VALUES
      (?, ?, ?, ?, ?, ?)
    ";

    $stmt = $this->connection->prepare($query);

    foreach ($table->getColumns() as $column) {
      if (!$stmt->execute([
        $column->getName(),
        $column->getType(),
        intval($column->isNull()),
        intval($column->isPrimary()),
        intval($column->isAutoIncrement()),
        $table->getTableId()
      ])) {
        $this->connection->rollBack();
        return null;
      }
    }

    $this->connection->commit();

    // содание таблицы в базе данных
    $query = "SELECT * FROM `Database` WHERE `database_id` = ?";
    $stmt = $this->connection->prepare($query);

    if (!$stmt->execute([$table->getDatabaseId()])) {
      $this->connection->rollBack();
      return null;
    }

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC)[0];
    $columns = array_map(function (Column $column) {
      $columnDesc =  "`{$column->getName()}` {$column->getType()}";

      if (!$column->isNull()) {
        $columnDesc .= " NOT NULL";
      }

      if ($column->isPrimary()) {
        $columnDesc .= " PRIMARY KEY";
      }

      if ($column->isAutoIncrement()) {
        $columnDesc .= " AUTO_INCREMENT";
      }

      return $columnDesc;
    }, $table->getColumns());

    $columns = implode(", ", $columns);
    $query = "CREATE TABLE `{$result['name']}`.`{$table->getName()}`($columns)";
    $this->dbConnection->query($query);

    return $table;
  }

  /**
   * Возвращает список таблиц
   * @return array
   */
  public function readAll(): array {
    $query = "
    SELECT
      `Table`.`table_id`,
      `Table`.`name` AS `table_name`,
      `Table`.`database_id`,
      `Column`.`name` AS `column_name`,
      `Column`.`type`,
      `Column`.`null`,
      `Column`.`primary`,
      `Column`.`auto_increment`
    FROM `Table`
    JOIN `Column`
      ON `Table`.`table_id` = `Column`.`table_id`;
    ";

    $stmt = $this->connection->query($query);

    if (!$stmt) {
      return [];
    }

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $tables = [];

    foreach ($result as $row) {
      $table = new Table();
      $table->setTableId($row["table_id"]);
      $table->setName($row["table_name"]);
      $table->setDatabaseId($row["database_id"]);

      $tables[$row["table_id"]] = $table;
    }

    foreach ($result as $row) {
      $column = new Column(
        $row["column_name"],
        $row["type"],
        $row["null"],
        $row["primary"],
        $row["auto_increment"]
      );
      $tables[$row["table_id"]]->addColumn($column);
    }

    return array_values($tables);
  }

  /**
   * Возвращает объект таблицы с заданным ID
   * @param int $tableId       ID таблицы
   * @return Table|null
   */
  public function readOne(int $tableId): ?Table {
    if (!$this->exists($tableId)) {
      return null;
    }

    $query = "
    SELECT
      `Table`.`table_id`,
      `Table`.`name` AS `table_name`,
      `Table`.`database_id`,
      `Column`.`name` AS `column_name`,
      `Column`.`type`,
      `Column`.`null`,
      `Column`.`primary`,
      `Column`.`auto_increment`
    FROM `Table`
    JOIN `Column`
      ON `Table`.`table_id` = `Column`.`table_id`
    WHERE `Table`.`table_id` = ?
    ";

    $stmt = $this->connection->prepare($query);

    if (!$stmt->execute([$tableId])) {
      return null;
    }

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $table = new Table();
    $table->setTableId($result[0]["table_id"]);
    $table->setName($result[0]["table_name"]);
    $table->setDatabaseId($result[0]["database_id"]);

    foreach ($result as $row) {
      $column = new Column(
        $row["column_name"],
        $row["type"],
        $row["null"],
        $row["primary"],
        $row["auto_increment"]
      );

      $table->addColumn($column);
    }

    return $table;
  }

  /**
   * Список таблиц определённой базы данных
   * @param int $databaseId
   * @return array
   */
  public function getDatabaseTables(int $databaseId): array {
    $query = "
    SELECT
      `Table`.`table_id`,
      `Table`.`name` AS `table_name`,
      `Table`.`database_id`,
      `Column`.`name` AS `column_name`,
      `Column`.`type`,
      `Column`.`null`,
      `Column`.`primary`,
      `Column`.`auto_increment`
    FROM `Table`
    JOIN `Column`
      ON `Table`.`table_id` = `Column`.`table_id`
    WHERE `Table`.`database_id` = ?
    ";

    $stmt = $this->connection->prepare($query);

    if (!$stmt->execute([$databaseId])) {
      return [];
    }

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $tables = [];

    foreach ($result as $row) {
      $table = new Table();
      $table->setTableId($row["table_id"]);
      $table->setName($row["table_name"]);
      $table->setDatabaseId($row["database_id"]);

      $tables[$row["table_id"]] = $table;
    }

    foreach ($result as $row) {
      $column = new Column(
        $row["column_name"],
        $row["type"],
        $row["null"],
        $row["primary"],
        $row["auto_increment"]
      );
      $tables[$row["table_id"]]->addColumn($column);
    }

    return array_values($tables);
  }

  /**
   * Удаляет таблицу из БД
   * @param int $tableId
   * @return bool
   */
  public function delete(int $tableId): bool {
    if (!$this->exists($tableId)) {
      return false;
    }

    $table = $this->readOne($tableId);
    $query = "SELECT * FROM `Database` WHERE `database_id` = ?";

    $stmt = $this->connection->prepare($query);
    if (!$stmt->execute([$table->getDatabaseId()])) {
      return false;
    }

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC)[0];
    $tableName = "`{$result['name']}`.`{$table->getName()}`";

    $this->connection->beginTransaction();

    $query = "DELETE FROM `Column` WHERE `table_id` = ?";
    $stmt = $this->connection->prepare($query);

    if (!$stmt->execute([$tableId])) {
      $this->connection->rollBack();
      return false;
    }

    $query = "DELETE FROM `Table` WHERE `table_id` = ?";
    $stmt = $this->connection->prepare($query);

    if (!$stmt->execute([$tableId])) {
      $this->connection->rollBack();
      return false;
    }

    $this->connection->commit();

    $query = "DROP TABLE $tableName";
    $this->dbConnection->query($query);

    return true;
  }
}