<?php

namespace Api\Dao;

require_once __DIR__ . "/../models/OrderConsole.php";
require_once __DIR__ . "/../models/Console.php";
require_once __DIR__ . "/../models/Client.php";
require_once __DIR__ . "/../models/Address.php";

use PDO;
use Api\Models\Console;
use Api\Models\User;
use Api\Models\Address;
use Api\Models\OrderConsole;

class OrderConsoleDAO {
  private PDO $connection;

  /**
   * Проверяет, существует ли заказ с указанным ID
   * @param int $orderId
   * @return bool
   */
  private function exists(int $orderId): bool {
    $query = "SELECT * FROM `OrderConsole` WHERE `order_id` = ?";
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
   * @param OrderConsole $order
   * @return OrderConsole|null
   */
  public function create(OrderConsole $order): ?OrderConsole {
    $query = "
      INSERT INTO `OrderConsole`(
       `console_id`,
       `client_id`,
       `amount`
      )
      VALUES
        (?, ?, ?)
    ";

    $stmt = $this->connection->prepare($query);

    if (!$stmt->execute([
      $order->getConsole()->getConsoleId(),
      $order->getClient()->getClientId(),
      $order->getAmount()
    ])) {
      return null;
    }

    $order->setOrderId($this->connection->lastInsertId());
    return $order;
  }

  /**
   * Возвращает список заказов консолей
   * @return array
   */
  public function readAll(): array {
    $query = "
      SELECT
	      `Client`.`client_id` AS `id`,
        `Client`.`first_name`,
        `Client`.`last_name`,
        `Client`.`email`,
        `Address`.`region`,
        `Address`.`city`,
        `Address`.`street`,
        `Address`.`house`,
        `Address`.`flat`,
        `OrderConsole`.`order_id` AS `order`,
        `OrderConsole`.`amount`,
        `Console`.`console_id` AS `console`,
        `Console`.`name`,
        `Console`.`brand`,
        `Console`.`gpu`,
        `Console`.`cpu`,
        `Console`.`ram`,
        `Console`.`price`
      FROM `Client`
      JOIN `Address`
	      ON `Client`.`client_id` = `Address`.`client_id`
      JOIN `OrderConsole`
	      ON `OrderConsole`.`client_id` = `Client`.`client_id`
      JOIN `Console`
	      ON `Console`.`console_id` = `OrderConsole`.`console_id`
      ORDER BY `order`";

    $stmt = $this->connection->query($query);

    if (!$stmt) {
      return [];
    }

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $orders = [];

    foreach ($result as $row) {
      $console = new Console(
        $row["console"],
        $row["name"],
        $row["brand"],
        $row["gpu"],
        $row["cpu"],
        $row["ram"],
        $row["price"]
      );

      $client = new User(
        $row["id"],
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

      $orders[] = new OrderConsole(
        $row["order"],
        $console,
        $client,
        $row["amount"]
      );
    }

    return $orders;
  }

  /**
   * Возвращает объект заказа консоли с заданным ID
   * @param int $orderId
   * @return OrderConsole|null
   */
  public function readOne(int $orderId): ?OrderConsole {
    if (!$this->exists($orderId)) {
      return null;
    }

    $query = "
      SELECT
	      `Client`.`client_id` AS `id`,
        `Client`.`first_name`,
        `Client`.`last_name`,
        `Client`.`email`,
        `Address`.`region`,
        `Address`.`city`,
        `Address`.`street`,
        `Address`.`house`,
        `Address`.`flat`,
        `OrderConsole`.`order_id` AS `order`,
        `OrderConsole`.`amount`,
        `Console`.`console_id` AS `console`,
        `Console`.`name`,
        `Console`.`brand`,
        `Console`.`gpu`,
        `Console`.`cpu`,
        `Console`.`ram`,
        `Console`.`price`
      FROM `Client`
      JOIN `Address`
	      ON `Client`.`client_id` = `Address`.`client_id`
      JOIN `OrderConsole`
	      ON `OrderConsole`.`client_id` = `Client`.`client_id`
      JOIN `Console`
	      ON `Console`.`console_id` = `OrderConsole`.`console_id`
      WHERE `OrderConsole`.`order_id` = ?
      ORDER BY `order`";

    $stmt = $this->connection->prepare($query);

    if (!$stmt->execute([$orderId])) {
      return null;
    }

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC)[0];

    $console = new Console(
      $result["console"],
      $result["name"],
      $result["brand"],
      $result["gpu"],
      $result["cpu"],
      $result["ram"],
      $result["price"]
    );

    $client = new User(
      $result["id"],
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

    return new OrderConsole(
      $result["order"],
      $console,
      $client,
      $result["amount"]
    );
  }

  /**
   * Обновляет данные заказа консоли в БД
   * @param OrderConsole $order
   * @return OrderConsole|null
   */
  public function update(OrderConsole $order): ?OrderConsole {
    if (!$this->exists($order->getOrderId())) {
      return null;
    }

    $query = "
      UPDATE
        `OrderConsole`
      SET
        `console_id` = ?,
        `client_id` = ?,
        `amount` = ?
      WHERE
        `order_id` = ?
    ";

    $stmt = $this->connection->prepare($query);

    if (!$stmt->execute([
      $order->getConsole()->getConsoleId(),
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

    $query = "DELETE FROM `OrderConsole` WHERE `order_id` = ?";
    $stmt = $this->connection->prepare($query);

    return $stmt->execute([$orderId]);
  }
}
