<?php

namespace Api\Dao;

use PDO;
use Api\Models\WiredGamepad;

class WiredGamepadDAO {
  private PDO $connection;

  /**
   * Проверяет, существует ли геймпад с указанным ID
   * @param int $gamepadId
   * @return bool
   */
  private function exists(int $gamepadId): bool {
    $query = "SELECT * FROM `WiredGamepad` WHERE `gamepad_id` = ?";
    $stmt = $this->connection->prepare($query);

    if (!$stmt->execute([$gamepadId])) {
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
   * Создаёт объект геймпада и добавляет его в БД
   * @param WiredGamepad $gamepad
   * @return WiredGamepad|null
   */
  public function create(WiredGamepad $gamepad): WiredGamepad|null {
    $this->connection->beginTransaction();

    $query = "
      INSERT INTO `Gamepad`(
        `name`,
        `brand`,
        `buttons`,
        `price`,
        `image`
      )
      VALUES
        (?, ?, ?, ?, ?)
    ";

    $stmt = $this->connection->prepare($query);

    if (!$stmt->execute([
      $gamepad->getName(),
      $gamepad->getBrand(),
      $gamepad->getButtons(),
      $gamepad->getPrice(),
      $gamepad->getImage()
    ])) {
      $this->connection->rollBack();
      return null;
    }

    $gamepad->setGamepadId($this->connection->lastInsertId());

    $query = "
      INSERT INTO `WiredGamepad`(
        `gamepad_id`,
        `cabel_length`,
        `consumption`
      )
      VALUES
        (?, ?, ?)
    ";

    $stmt = $this->connection->prepare($query);

    if (!$stmt->execute([
      $gamepad->getGamepadId(),
      $gamepad->getCabelLength(),
      $gamepad->getConsumption()
    ])) {
      $this->connection->rollBack();
      return null;
    }

    $this->connection->commit();
    return $gamepad;
  }

  /**
   * Возвращает список геймпадов
   * @return array
   */
  public function readAll(): array {
    $query = "
    SELECT
      `Gamepad`.`gamepad_id` AS `id`,
      `Gamepad`.`name`,
      `Gamepad`.`brand`,
      `Gamepad`.`buttons`,
      `Gamepad`.`price`,
      `Gamepad`.`image`,
      `WiredGamepad`.`cabel_length`,
      `WiredGamepad`.`consumption`
    FROM `Gamepad`
    JOIN `WiredGamepad`
      ON `Gamepad`.`gamepad_id` = `WiredGamepad`.`gamepad_id`";

    $stmt = $this->connection->query($query);

    if (!$stmt) {
      return [];
    }

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $gamepads = [];

    foreach ($result as $row) {
      $gamepads[] = new WiredGamepad(
        $row["id"],
        $row["name"],
        $row["brand"],
        $row["buttons"],
        $row["price"],
        $row["image"],
        $row["cabel_length"],
        $row["consumption"]
      );
    }

    return $gamepads;
  }

  /**
   * Возвращает объект геймпада с заданным ID
   * @param int $gamepadId       ID консоли
   * @return WiredGamepad|null
   */
  public function readOne(int $gamepadId): WiredGamepad|null {
    if (!$this->exists($gamepadId)) {
      return null;
    }

    $query = "
    SELECT
      `Gamepad`.`gamepad_id` AS `id`,
      `Gamepad`.`name`,
      `Gamepad`.`brand`,
      `Gamepad`.`buttons`,
      `Gamepad`.`price`,
      `Gamepad`.`image`,
      `WiredGamepad`.`cabel_length`,
      `WiredGamepad`.`consumption`
    FROM `Gamepad`
    JOIN `WiredGamepad`
      ON `Gamepad`.`gamepad_id` = `WiredGamepad`.`gamepad_id`
    WHERE `Gamepad`.`gamepad_id` = ?";

    $stmt = $this->connection->prepare($query);

    if (!$stmt->execute([$gamepadId])) {
      return null;
    }

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC)[0];
    return new WiredGamepad(
      $result["id"],
      $result["name"],
      $result["brand"],
      $result["buttons"],
      $result["price"],
      $result["image"],
      $result["cabel_length"],
      $result["consumption"]
    );
  }

  /**
   * Обновляет данные консоли в БД
   * @param WiredGamepad $gamepad
   * @return WiredGamepad|null
   */
  public function update(WiredGamepad $gamepad): WiredGamepad|null {
    if (!$this->exists($gamepad->getGamepadId())) {
      return null;
    }

    $this->connection->beginTransaction();

    $query = "
      UPDATE
        `Gamepad`
      SET
        `name` = ?,
        `brand` = ?,
        `buttons` = ?,
        `price` = ?,
        `image` = ?
      WHERE `gamepad_id` = ?
    ";

    $stmt = $this->connection->prepare($query);

    if (!$stmt->execute([
      $gamepad->getName(),
      $gamepad->getBrand(),
      $gamepad->getButtons(),
      $gamepad->getPrice(),
      $gamepad->getImage(),
      $gamepad->getGamepadId()
    ])) {
      $this->connection->rollBack();
      return null;
    }

    $query = "
      UPDATE
        `WiredGamepad`
      SET
        `cabel_length` = ?,
        `consumption` = ?
      WHERE `gamepad_id` = ?
    ";

    $stmt = $this->connection->prepare($query);

    if (!$stmt->execute([
      $gamepad->getCabelLength(),
      $gamepad->getConsumption(),
      $gamepad->getGamepadId()
    ])) {
      $this->connection->rollBack();
      return null;
    }

    $this->connection->commit();
    return $gamepad;
  }

  /**
   * Удаляет данные геймпада из БД
   * @param int $gamepadId
   * @return bool
   */
  public function delete(int $gamepadId): bool {
    if (!$this->exists($gamepadId)) {
      return false;
    }

    $this->connection->beginTransaction();

    $query = "DELETE FROM `WiredGamepad` WHERE `gamepad_id` = ?";
    $stmt = $this->connection->prepare($query);

    if (!$stmt->execute([$gamepadId])) {
      $this->connection->rollBack();
      return false;
    }

    $query = "DELETE FROM `Gamepad` WHERE `gamepad_id` = ?";
    $stmt = $this->connection->prepare($query);

    if (!$stmt->execute([$gamepadId])) {
      $this->connection->rollBack();
      return false;
    }

    $this->connection->commit();
    return true;
  }
}
