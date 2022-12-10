<?php

namespace Api\Dao;

require_once __DIR__ . "/../models/User.php";
require_once __DIR__ . "/../models/Role.php";
require_once __DIR__ . "/../models/Database.php";

use mysql_xdevapi\DatabaseObject;
use PDO;
use mysqli;
use Api\Models\User;
use Api\Models\Role;
use Api\Models\Database;

class DatabaseDAO {
  private PDO $connection;
  private mysqli $dbConnection;

  /**
   * Проверяет, существует ли база данных с указанным ID
   * @param int $databaseId
   * @return bool
   */
  private function exists(int $databaseId): bool {
    $query = "SELECT * FROM `Database` WHERE `database_id` = ?";
    $stmt = $this->connection->prepare($query);

    if (!$stmt->execute([$databaseId])) {
      return false;
    }

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return count($result) > 0;
  }

  /**
   * @param PDO $connection       подключение к БД
   * @param mysqli $dbConnection  подключение к БД (ко всей базе данных)
   */
  public function __construct(PDO $connection, mysqli $dbConnection) {
    $this->connection = $connection;
    $this->dbConnection = $dbConnection;
  }

  /**
   * Создаёт объект базы данных и добавляет её в БД
   * @param Database $database
   * @return Database|null
   */
  public function create(Database $database): ?Database {
    $query = "
      INSERT INTO `Database`(
        `name`,
        `user_id`
      )
      VALUES
        (?, ?)
    ";

    $stmt = $this->connection->prepare($query);

    if (!$stmt->execute([
      $database->getName(),
      $database->getUser()->getUserId()
    ])) {
      $this->connection->rollBack();
      return null;
    }

    $database->setDatabaseId($this->connection->lastInsertId());
    $this->dbConnection->query("CREATE DATABASE `{$database->getName()}`");

    return $database;
  }

  /**
   * Возвращает список баз данных
   * @return array
   */
  public function readAll(): array {
    $query = "
    SELECT
      `Database`.`database_id`,
      `Database`.`name`,
      `User`.`user_id`,
      `User`.`username`,
      `User`.`email`,
      `Role`.`role_id`,
      `Role`.`title`,
      `Role`.`description`
    FROM `Database`
    JOIN `User`
      ON `Database`.`user_id` = `User`.`user_id`
    JOIN `Role`
      ON `User`.`role_id` = `Role`.`role_id`
    ORDER BY `Database`.`database_id`
    ";

    $stmt = $this->connection->query($query);

    if (!$stmt) {
      return [];
    }

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $databases = [];

    foreach ($result as $row) {
      $databases[] = new Database(
        $row["database_id"],
        $row["name"],
        new User(
          $row["user_id"],
          $row["username"],
          $row["email"],
          "",
          new Role(
            $row["role_id"],
            $row["title"],
            $row["description"]
          )
        )
      );
    }

    return $databases;
  }

  /**
   * Возвращает объект базы данных с заданным ID
   * @param int $databaseId       ID пользователя
   * @return Database|null
   */
  public function readOne(int $databaseId): ?Database {
    if (!$this->exists($databaseId)) {
      return null;
    }

    $query = "
    SELECT
      `Database`.`database_id`,
      `Database`.`name`,
      `User`.`user_id`,
      `User`.`username`,
      `User`.`email`,
      `Role`.`role_id`,
      `Role`.`title`,
      `Role`.`description`
    FROM `Database`
    JOIN `User`
      ON `Database`.`user_id` = `User`.`user_id`
    JOIN `Role`
      ON `User`.`role_id` = `Role`.`role_id`
    WHERE `Database`.`database_id` = ?
    ";

    $stmt = $this->connection->prepare($query);

    if (!$stmt->execute([$databaseId])) {
      return null;
    }

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC)[0];

    return new Database(
      $result["database_id"],
      $result["name"],
      new User(
        $result["user_id"],
        $result["username"],
        $result["email"],
        "",
        new Role(
          $result["role_id"],
          $result["title"],
          $result["description"]
        )
      )
    );
  }

  /**
   * Возвращает список баз данных, принадлежащих определённому пользователю
   * @param int $userId
   * @return array
   */
  public function getUserDatabases(int $userId): array {
    $query = "
    SELECT
      `Database`.`database_id`,
      `Database`.`name`,
      `User`.`user_id`,
      `User`.`username`,
      `User`.`email`,
      `Role`.`role_id`,
      `Role`.`title`,
      `Role`.`description`
    FROM `Database`
    JOIN `User`
      ON `Database`.`user_id` = `User`.`user_id`
    JOIN `Role`
      ON `User`.`role_id` = `Role`.`role_id`
    WHERE `Database`.`user_id` = ?
    ";

    $stmt = $this->connection->prepare($query);

    if (!$stmt->execute([$userId])) {
      return [];
    }

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $databases = [];

    foreach ($result as $row) {
      $databases[] = new Database(
        $row["database_id"],
        $row["name"],
        new User(
          $row["user_id"],
          $row["username"],
          $row["email"],
          "",
          new Role(
            $row["role_id"],
            $row["title"],
            $row["description"]
          )
        )
      );
    }

    return $databases;
  }

  /**
   * Обновляет данные базы данных в БД
   * @param Database $database
   * @return Database|null
   */
  public function update(Database $database): ?Database {
    if (!$this->exists($database->getDatabaseId())) {
      return null;
    }

    $oldName = $this->readOne($database->getDatabaseId())->getName();

    $query = "
      UPDATE
        `Database`
      SET
        `name` = ?,
        `user_id` = ?
      WHERE `database_id` = ?
    ";

    $stmt = $this->connection->prepare($query);

    if (!$stmt->execute([
      $database->getName(),
      $database->getUser()->getUserId(),
      $database->getDatabaseId()
    ])) {
      return null;
    }

    if ($oldName !== $database->getName()) {
      $this->dbConnection->query("ALTER DATABASE `$oldName` MODIFY NAME = `{$database->getName()}`");
    }

    return $database;
  }

  /**
   * Удаляет базу данных из БД
   * @param int $databaseId
   * @return bool
   */
  public function delete(int $databaseId): bool {
    if (!$this->exists($databaseId)) {
      return false;
    }

    $dbName = $this->readOne($databaseId)->getName();

    $query = "DELETE FROM `Database` WHERE `database_id` = ?";
    $stmt = $this->connection->prepare($query);

    $this->dbConnection->query("DROP DATABASE `$dbName`");
    return $stmt->execute([$databaseId]);
  }
}