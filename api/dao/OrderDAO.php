<?php

namespace Api\Dao;

require_once __DIR__ . "/../models/Order.php";
require_once __DIR__ . "/../models/Client.php";
require_once __DIR__ . "/../models/Address.php";
require_once __DIR__ . "/../models/Gamepad.php";
require_once __DIR__ . "/../models/Console.php";

require_once __DIR__ . "/../models/OrderGamepad.php";
require_once __DIR__ . "/../models/OrderConsole.php";

use PDO;

use Api\Models\Order;
use Api\Models\User;
use Api\Models\Address;
use Api\Models\Gamepad;
use Api\Models\Console;

use Api\Models\OrderGamepad;
use Api\Models\OrderConsole;

class OrderDAO {
  private PDO $connection;

  /**
   * Проверяет, существует ли заказ с указанным ID
   * @param int $orderId
   * @return bool
   */
  private function exists(int $orderId): bool {
    $query = "SELECT * FROM `Order` WHERE `order_id` = ?";
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
   * Добавляет заказ в БД
   * @param Order $order
   * @return Order|null
   */
  public function create(Order $order): ?Order {
    $this->connection->beginTransaction();

    // вставка в таблицу Order
    $query = "INSERT INTO `Order`(`client_id`, `price`) VALUES (?, ?)";
    $stmt = $this->connection->prepare($query);

    if (!$stmt->execute([
      $order->getClient()->getClientId(),
      $order->getPrice()
    ])) {
      $this->connection->rollBack();
      return null;
    }

    // id добавленного заказа
    $orderId = $this->connection->lastInsertId();

    // заказанные консоли
    $ordersConsole = $order->getOrdersConsole();

    $query = "INSERT INTO `OrderConsole`(`order_id`, `console_id`, `amount`) VALUES (?, ?, ?)";
    $stmt = $this->connection->prepare($query);

    foreach ($ordersConsole as $orderConsole) {
      if (!$stmt->execute([
        $orderId,
        $orderConsole->getConsole()->getConsoleId(),
        $orderConsole->getAmount()
      ])) {
        $this->connection->rollBack();
        return null;
      }
    }

    // заказанные геймпады
    $ordersGamepad = $order->getOrdersGamepad();

    $query = "INSERT INTO `OrderGamepad`(`order_id`, `gamepad_id`, `amount`) VALUES (?, ?, ?)";
    $stmt = $this->connection->prepare($query);

    foreach ($ordersGamepad as $orderGamepad) {
      if (!$stmt->execute([
        $orderId,
        $orderGamepad->getGamepad()->getGamepadId(),
        $orderGamepad->getAmount()
      ])) {
        $this->connection->rollBack();
        return null;
      }
    }

    $order->setOrderId($orderId);
    $this->connection->commit();

    return $order;
  }

  /**
   * Возвращает список заказов
   * @return array
   */
  public function readAll(): array {
    // запрос для получения данных клиента, сделавшего заказ, и общих деталей заказа
    $clientsQuery = "
    SELECT
      `Order`.`order_id`,
      `Client`.`client_id`,
      `Client`.`first_name`,
      `Client`.`last_name`,
      `Client`.`email`,
      `Address`.`region`,
      `Address`.`city`,
      `Address`.`street`,
      `Address`.`house`,
      `Address`.`flat`,
      `Order`.`price`
    FROM `Order`
    JOIN `Client`
      ON `Order`.`client_id` = `Client`.`client_id`
    JOIN `Address`
      ON `Order`.`client_id` = `Address`.`client_id`
    ORDER BY `Order`.`order_id`;
    ";

    $stmt = $this->connection->query($clientsQuery);
    if (!$stmt) {
      return [];
    }

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $orders = [];

    // заполнение заказа общими данными и данными клиента
    foreach ($result as $row) {
      $order = new Order();
      $order->setOrderId($row["order_id"]);

      $order->setClient(new User(
        $row["client_id"],
        $row["first_name"],
        $row["last_name"],
        $row["email"],
        new Address(
          $row["region"],
          $row["city"],
          $row["street"],
          $row["house"],
          $row["flat"]
        )
      ));

      $order->setPrice($row["price"]);
      $orders[$row["order_id"]] = $order;
    }

    // запрос для получения данных заказанных консолей
    $consolesQuery = "
    SELECT
      `OrderConsole`.`order_id`,
      `Console`.`console_id`,
      `Console`.`name`,
      `Console`.`brand`,
      `Console`.`gpu`,
      `Console`.`cpu`,
      `Console`.`ram`,
      `Console`.`price`,
      `OrderConsole`.`amount`
    FROM `OrderConsole`
    JOIN `Console`
      ON `OrderConsole`.`console_id` = `Console`.`console_id`
    ORDER BY `OrderConsole`.`order_id`;
    ";

    $stmt = $this->connection->query($consolesQuery);
    if (!$stmt) {
      return [];
    }

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // добавление в заказ данных консоли
    foreach ($result as $row) {
      $console = new Console(
        $row["console_id"],
        $row["name"],
        $row["brand"],
        $row["gpu"],
        $row["cpu"],
        $row["ram"],
        $row["price"]
      );

      $orderConsole = new OrderConsole(
        $console,
        $row["amount"]
      );

      $orders[$row["order_id"]]->addOrderConsole($orderConsole);
    }

    // запрос для получения данных заказанного геймпада
    $gamepadsQuery = "
    SELECT
      `OrderGamepad`.`order_id`,
      `Gamepad`.`gamepad_id`,
      `Gamepad`.`name`,
      `Gamepad`.`brand`,
      `Gamepad`.`buttons`,
      `Gamepad`.`price`,
      `OrderGamepad`.`amount`
    FROM `OrderGamepad`
    JOIN `Gamepad`
      ON `OrderGamepad`.`gamepad_id` = `Gamepad`.`gamepad_id`
    ORDER BY `OrderGamepad`.`order_id`;
    ";

    $stmt = $this->connection->query($gamepadsQuery);
    if (!$stmt) {
      return [];
    }

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // добавление в заказ данных геймпада
    foreach ($result as $row) {
      $gamepad = new Gamepad(
        $row["gamepad_id"],
        $row["name"],
        $row["brand"],
        $row["buttons"],
        $row["price"]
      );

      $orderGamepad = new OrderGamepad(
        $gamepad,
        $row["amount"]
      );

      $orders[$row["order_id"]]->addOrderGamepad($orderGamepad);
    }

    return array_values($orders);
  }

  /**
   * Возвращает заказ по его id
   * @param int $orderId
   * @return Order|null
   */
  public function readOne(int $orderId): ?Order {
    if (!$this->exists($orderId)) {
      return null;
    }

    // запрос для получения данных клиента, сделавшего заказ, и общих деталей заказа
    $clientsQuery = "
    SELECT
      `Order`.`order_id`,
      `Client`.`client_id`,
      `Client`.`first_name`,
      `Client`.`last_name`,
      `Client`.`email`,
      `Address`.`region`,
      `Address`.`city`,
      `Address`.`street`,
      `Address`.`house`,
      `Address`.`flat`,
      `Order`.`price`
    FROM `Order`
    JOIN `Client`
      ON `Order`.`client_id` = `Client`.`client_id`
    JOIN `Address`
      ON `Order`.`client_id` = `Address`.`client_id`
    WHERE `Order`.`order_id` = ?
    ";

    $stmt = $this->connection->prepare($clientsQuery);
    if (!$stmt->execute([$orderId])) {
      return null;
    }

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC)[0];

    // заполнение заказа общими деталями и данными клиента
    $order = new Order();
    $order->setOrderId($result["order_id"]);

    $order->setClient(new User(
      $result["client_id"],
      $result["first_name"],
      $result["last_name"],
      $result["email"],
      new Address(
        $result["region"],
        $result["city"],
        $result["street"],
        $result["house"],
        $result["flat"]
      )
    ));

    $order->setPrice($result["price"]);

    // запрос для получения данных заказанных консолей
    $consolesQuery = "
    SELECT
      `OrderConsole`.`order_id`,
      `Console`.`console_id`,
      `Console`.`name`,
      `Console`.`brand`,
      `Console`.`gpu`,
      `Console`.`cpu`,
      `Console`.`ram`,
      `Console`.`price`,
      `OrderConsole`.`amount`
    FROM `OrderConsole`
    JOIN `Console`
      ON `OrderConsole`.`console_id` = `Console`.`console_id`
    WHERE `OrderConsole`.`order_id` = ?
    ";

    $stmt = $this->connection->prepare($consolesQuery);
    if (!$stmt->execute([$orderId])) {
      return null;
    }

    // добавление консолей в заказ
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($result as $row) {
      $console = new Console(
        $row["console_id"],
        $row["name"],
        $row["brand"],
        $row["gpu"],
        $row["cpu"],
        $row["ram"],
        $row["price"]
      );

      $orderConsole = new OrderConsole(
        $console,
        $row["amount"]
      );

      $order->addOrderConsole($orderConsole);
    }

    // запрос для получения данных заказанного геймпада
    $gamepadsQuery = "
    SELECT
      `OrderGamepad`.`order_id`,
      `Gamepad`.`gamepad_id`,
      `Gamepad`.`name`,
      `Gamepad`.`brand`,
      `Gamepad`.`buttons`,
      `Gamepad`.`price`,
      `OrderGamepad`.`amount`
    FROM `OrderGamepad`
    JOIN `Gamepad`
      ON `OrderGamepad`.`gamepad_id` = `Gamepad`.`gamepad_id`
    WHERE `OrderGamepad`.`order_id` = ?
    ";

    $stmt = $this->connection->prepare($gamepadsQuery);
    if (!$stmt->execute([$orderId])) {
      return null;
    }

    // добавление заказанных геймпадов
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($result as $row) {
      $gamepad = new Gamepad(
        $row["gamepad_id"],
        $row["name"],
        $row["brand"],
        $row["buttons"],
        $row["price"]
      );

      $orderGamepad = new OrderGamepad(
        $gamepad,
        $row["amount"]
      );

      $order->addOrderGamepad($orderGamepad);
    }

    return $order;
  }

  /**
   * Удаляет заказ из БД
   * @param int $orderId
   * @return bool
   */
  public function delete(int $orderId): bool {
    if (!$this->exists($orderId)) {
      return false;
    }

    $this->connection->beginTransaction();

    $query = "DELETE FROM `OrderConsole` WHERE `order_id` = ?";
    $stmt = $this->connection->prepare($query);

    if (!$stmt->execute([$orderId])) {
      $this->connection->rollBack();
      return false;
    }

    $query = "DELETE FROM `OrderGamepad` WHERE `order_id` = ?";
    $stmt = $this->connection->prepare($query);

    if (!$stmt->execute([$orderId])) {
      $this->connection->rollBack();
      return false;
    }

    $query = "DELETE FROM `Order` WHERE `order_id` = ?";
    $stmt = $this->connection->prepare($query);

    if (!$stmt->execute([$orderId])) {
      $this->connection->rollBack();
      return false;
    }

    $this->connection->commit();
    return true;
  }
}
