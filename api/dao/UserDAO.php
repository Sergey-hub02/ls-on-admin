<?php

namespace Api\Dao;

require_once __DIR__ . "/../models/User.php";
require_once __DIR__ . "/../models/Role.php";

use PDO;
use Api\Models\User;
use Api\Models\Role;

class UserDAO {
  private PDO $connection;

  /**
   * Проверяет, существует ли пользователь с указанным ID
   * @param int $userId
   * @return bool
   */
  private function exists(int $userId): bool {
    $query = "SELECT * FROM `User` WHERE `user_id` = ?";
    $stmt = $this->connection->prepare($query);

    if (!$stmt->execute([$userId])) {
      return false;
    }

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return count($result) > 0;
  }

  /**
   * @param PDO $connection       подключение к БД
   */
  public function __construct(PDO $connection) {
    $this->connection = $connection;
  }

  /**
   * Создаёт объект пользователя и добавляет его в БД
   * @param User $user
   * @return User|null
   */
  public function create(User $user): ?User {
    $query = "
      INSERT INTO `User`(
        `username`,
        `email`,
        `password`,
        `role_id`
      )
      VALUES
        (?, ?, ?, ?)
    ";

    $stmt = $this->connection->prepare($query);

    if (!$stmt->execute([
      $user->getUsername(),
      $user->getEmail(),
      $user->getPassword(),
      $user->getRole()->getRoleId()
    ])) {
      return null;
    }

    $user->setUserId($this->connection->lastInsertId());
    return $user;
  }

  /**
   * Возвращает список пользователей
   * @return array
   */
  public function readAll(): array {
    $query = "
    SELECT
      `User`.`user_id`,
      `User`.`username`,
      `User`.`email`,
      `User`.`role_id`,
      `Role`.`title`,
      `Role`.`description`
    FROM `User`
    JOIN `Role`
      ON `User`.`role_id` = `Role`.`role_id`
    ORDER BY `User`.`user_id`
    ";

    $stmt = $this->connection->query($query);

    if (!$stmt) {
      return [];
    }

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $users = [];

    foreach ($result as $row) {
      $users[] = new User(
        $row["user_id"],
        $row["username"],
        $row["email"],
        "",
        new Role(
          $row["role_id"],
          $row["title"],
          $row["description"]
        )
      );
    }

    return $users;
  }

  /**
   * Возвращает объект пользователя с заданным ID
   * @param int $userId       ID пользователя
   * @return User|null
   */
  public function readOne(int $userId): ?User {
    if (!$this->exists($userId)) {
      return null;
    }

    $query = "
      SELECT
        `User`.`user_id`,
        `User`.`username`,
        `User`.`email`,
        `User`.`password`,
        `User`.`role_id`,
        `Role`.`title`,
        `Role`.`description`
      FROM `User`
      JOIN `Role`
        ON `User`.`role_id` = `Role`.`role_id`
      WHERE `User`.`user_id` = ?
      ORDER BY `User`.`user_id`
    ";

    $stmt = $this->connection->prepare($query);

    if (!$stmt->execute([$userId])) {
      return null;
    }

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC)[0];

    return new User(
      $result["user_id"],
      $result["username"],
      $result["email"],
      $result["password"],
      new Role(
        $result["role_id"],
        $result["title"],
        $result["description"]
      )
    );
  }

  /**
   * Обновляет данные пользователя в БД
   * @param User $user
   * @return User|null
   */
  public function update(User $user): ?User {
    if (!$this->exists($user->getUserId())) {
      return null;
    }

    $query = "
      UPDATE
        `User`
      SET
        `username` = ?,
        `email` = ?,
        `password` = ?,
        `role_id` = ?
      WHERE `user_id` = ?
    ";

    $stmt = $this->connection->prepare($query);

    if (!$stmt->execute([
      $user->getUsername(),
      $user->getEmail(),
      $user->getPassword(),
      $user->getRole()->getRoleId(),
      $user->getUserId()
    ])) {
      return null;
    }

    return $user;
  }

  /**
   * Удаляет данные пользователя из БД
   * @param int $userId
   * @return bool
   */
  public function delete(int $userId): bool {
    if (!$this->exists($userId)) {
      return false;
    }

    $query = "DELETE FROM `User` WHERE `user_id` = ?";
    $stmt = $this->connection->prepare($query);

    return $stmt->execute([$userId]);
  }
}
