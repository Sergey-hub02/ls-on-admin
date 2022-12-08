<?php

namespace Api\Dao;

require_once __DIR__ . "/../models/OrderGamepad.php";
require_once __DIR__ . "/../models/Gamepad.php";
require_once __DIR__ . "/../models/Client.php";
require_once __DIR__ . "/../models/Address.php";

use PDO;

use Api\Models\Gamepad;

use Api\Models\User;
use Api\Models\Address;

use Api\Models\OrderGamepad;

class OrderGamepadDAO {
  private PDO $connection;

  /**
   * Проверяет, существует ли заказ с указанным ID
   * @param int $orderId
   * @return bool
   */
  private function exists(int $orderId): bool {
    $query = "SELECT * FROM `OrderGamepad` WHERE `order_id` = ?";
    $stmt = $this->connection->prepare($query);

    if (!$stmt->execute([$orderId])) {
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
   * Создаёт объект заказа и добавляет его в БД
   * @param OrderGamepad $order
   * @return OrderGamepad|null
   */
  public function create(OrderGamepad $order): ?OrderGamepad {
    $query = "
      INSERT INTO `OrderGamepad`(
       `gamepad_id`,
       `client_id`,
       `amount`
      )
      VALUES
        (?, ?, ?)
    ";

    $stmt = $this->connection->prepare($query);

    if (!$stmt->execute([
      $order->getGamepad()->getGamepadId(),
      $order->getClient()->getClientId(),
      $order->getAmount()
    ])) {
      return null;
    }

    $order->setOrderId($this->connection->lastInsertId());
    return $order;
  }

  /**
   * Возвращает список заказов геймпадов
   * @return array
   */
  public function readAll(): array {
    $query = "
      SELECT
	      `Client`.`client_id`,
        `Client`.`first_name`,
        `Client`.`last_name`,
        `Client`.`email`,
        `Address`.`region`,
        `Address`.`city`,
        `Address`.`street`,
        `Address`.`house`,
        `Address`.`flat`,
        `OrderGamepad`.`order_id`,
        `OrderGamepad`.`amount`,
        `Gamepad`.`gamepad_id`,
        `Gamepad`.`name`,
        `Gamepad`.`brand`,
        `Gamepad`.`buttons`,
        `Gamepad`.`price`
      FROM `OrderGamepad`
      JOIN `Client`
	      ON `OrderGamepad`.`client_id` = `Client`.`client_id`
      JOIN `Address`
	      ON `Client`.`client_id` = `address`.`client_id`
      JOIN `Gamepad`
	      ON `OrderGamepad`.`gamepad_id` = `Gamepad`.`gamepad_id`
      ORDER BY `OrderGamepad`.`order_id`";

    $stmt = $this->connection->query($query);

    if (!$stmt) {
      return [];
    }

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $orders = [];

    foreach ($result as $row) {
      $gamepad = new Gamepad(
        $row["gamepad_id"],
        $row["name"],
        $row["brand"],
        $row["buttons"],
        $row["price"]
      );

      $client = new User(
        $row["client_id"],
        $row["first_name"],
        $row["last_name"],
        $row["email"],
        "",
        new Address(
          $row["region"],
          $row["city"],
          $row["street"],
          $row["house"],
          $row["flat"]
        )
      );

      $orders[] = new OrderGamepad(
        $row["order_id"],
        $gamepad,
        $client,
        $row["amount"]
      );
    }

    return $orders;
  }

  /**
   * Возвращает объект заказа геймпада по его ID
   * @param int $gamepadId
   * @return OrderGamepad|null
   */
  public function readOne(int $gamepadId): ?OrderGamepad {
    if (!$this->exists($gamepadId)) {
      return null;
    }

    $query = "
      SELECT
	      `Client`.`client_id`,
        `Client`.`first_name`,
        `Client`.`last_name`,
        `Client`.`email`,
        `Address`.`region`,
        `Address`.`city`,
        `Address`.`street`,
        `Address`.`house`,
        `Address`.`flat`,
        `OrderGamepad`.`order_id`,
        `OrderGamepad`.`amount`,
        `Gamepad`.`gamepad_id`,
        `Gamepad`.`name`,
        `Gamepad`.`brand`,
        `Gamepad`.`buttons`,
        `Gamepad`.`price`
      FROM `OrderGamepad`
      JOIN `Client`
	      ON `OrderGamepad`.`client_id` = `Client`.`client_id`
      JOIN `Address`
	      ON `Client`.`client_id` = `address`.`client_id`
      JOIN `Gamepad`
	      ON `OrderGamepad`.`gamepad_id` = `Gamepad`.`gamepad_id`
      WHERE `OrderGamepad`.`order_id` = ?
      ORDER BY `OrderGamepad`.`order_id`";

    $stmt = $this->connection->prepare($query);

    if (!$stmt->execute([$gamepadId])) {
      return null;
    }

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC)[0];

    $gamepad = new Gamepad(
      $result["gamepad_id"],
      $result["name"],
      $result["brand"],
      $result["buttons"],
      $result["price"]
    );

    $client = new User(
      $result["client_id"],
      $result["first_name"],
      $result["last_name"],
      $result["email"],
      "",
      new Address(
        $result["region"],
        $result["city"],
        $result["street"],
        $result["house"],
        $result["flat"]
      )
    );

    return new OrderGamepad(
      $result["order_id"],
      $gamepad,
      $client,
      $result["amount"]
    );
  }

  /**
   * Обновляет данные заказа консоли в БД
   * @param OrderGamepad $order
   * @return OrderGamepad|null
   */
  public function update(OrderGamepad $order): ?OrderGamepad {
    if (!$this->exists($order->getOrderId())) {
      return null;
    }

    $query = "
      UPDATE
        `OrderGamepad`
      SET
        `gamepad_id` = ?,
        `client_id` = ?,
        `amount` = ?
      WHERE
        `order_id` = ?
    ";

    $stmt = $this->connection->prepare($query);

    if (!$stmt->execute([
      $order->getGamepad()->getGamepadId(),
      $order->getClient()->getClientId(),
      $order->getAmount(),
      $order->getOrderId()
    ])) {
      return null;
    }

    return $order;
  }

  /**
   * Удаляет данные заказа консоли из БД
   * @param int $orderId
   * @return bool
   */
  public function delete(int $orderId): bool {
    if (!$this->exists($orderId)) {
      return false;
    }

    $query = "DELETE FROM `OrderGamepad` WHERE `order_id` = ?";
    $stmt = $this->connection->prepare($query);

    return $stmt->execute([$orderId]);
  }
}
