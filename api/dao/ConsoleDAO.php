<?php

namespace Api\Dao;

require_once __DIR__ . "/../models/Console.php";

use PDO;
use Api\Models\Console;

class ConsoleDAO {
  private PDO $connection;

  /**
   * Проверяет, существует ли консоль с указанным ID
   * @param int $consoleId
   * @return bool
   */
  private function exists(int $consoleId): bool {
    $query = "SELECT * FROM `Console` WHERE `console_id` = ?";
    $stmt = $this->connection->prepare($query);

    if (!$stmt->execute([$consoleId])) {
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
   * Создаёт объект консоли и добавляет его в БД
   * @param Console $console
   * @return Console|null
   */
  public function create(Console $console): Console|null {
    $query = "
      INSERT INTO `Console`(
        `name`,
        `brand`,
        `gpu`,
        `cpu`,
        `ram`,
        `price`,
        `image`
      )
      VALUES
        (?, ?, ?, ?, ?, ?, ?)
    ";

    $stmt = $this->connection->prepare($query);

    if (!$stmt->execute([
      $console->getName(),
      $console->getBrand(),
      $console->getGpu(),
      $console->getCpu(),
      $console->getRam(),
      $console->getPrice(),
      $console->getImage()
    ])) {
      return null;
    }

    $console->setConsoleId($this->connection->lastInsertId());
    return $console;
  }

  /**
   * Возвращает список консолей
   * @return array
   */
  public function readAll(): array {
    $query = "
    SELECT
      `Console`.`console_id` AS `id`,
      `Console`.`name`,
      `Console`.`brand`,
      `Console`.`gpu`,
      `Console`.`cpu`,
      `Console`.`ram`,
      `Console`.`price`,
      `Console`.`image`
    FROM `Console`";

    $stmt = $this->connection->query($query);

    if (!$stmt) {
      return [];
    }

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $consoles = [];

    foreach ($result as $row) {
      $consoles[] = new Console(
        $row["id"],
        $row["name"],
        $row["brand"],
        $row["gpu"],
        $row["cpu"],
        $row["ram"],
        $row["price"],
        $row["image"]
      );
    }

    return $consoles;
  }

  /**
   * Возвращает объект консоли с заданным ID
   * @param int $consoleId       ID консоли
   * @return Console|null
   */
  public function readOne(int $consoleId): Console|null {
    if (!$this->exists($consoleId)) {
      return null;
    }

    $query = "
      SELECT
        `Console`.`console_id` AS `id`,
        `Console`.`name`,
        `Console`.`brand`,
        `Console`.`gpu`,
        `Console`.`cpu`,
        `Console`.`ram`,
        `Console`.`price`,
        `Console`.`image`
      FROM `Console`
      WHERE `Console`.`console_id` = ?";

    $stmt = $this->connection->prepare($query);

    if (!$stmt->execute([$consoleId])) {
      return null;
    }

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC)[0];
    return new Console(
      $result["id"],
      $result["name"],
      $result["brand"],
      $result["gpu"],
      $result["cpu"],
      $result["ram"],
      $result["price"],
      $result["image"]
    );
  }

  /**
   * Обновляет данные консоли в БД
   * @param Console $console
   * @return Console|null
   */
  public function update(Console $console): Console|null {
    if (!$this->exists($console->getConsoleId())) {
      return null;
    }

    $query = "
      UPDATE
        `Console`
      SET
        `name` = ?,
        `brand` = ?,
        `gpu` = ?,
        `cpu` = ?,
        `ram` = ?,
        `price` = ?,
        `image` = ?
      WHERE `console_id` = ?
    ";

    $stmt = $this->connection->prepare($query);

    if (!$stmt->execute([
      $console->getName(),
      $console->getBrand(),
      $console->getGpu(),
      $console->getCpu(),
      $console->getRam(),
      $console->getPrice(),
      $console->getImage(),
      $console->getConsoleId()
    ])) {
      return null;
    }

    return $console;
  }

  /**
   * Удаляет данные консоли из БД
   * @param int $consoleId
   * @return bool
   */
  public function delete(int $consoleId): bool {
    if (!$this->exists($consoleId)) {
      return false;
    }

    $query = "DELETE FROM `Console` WHERE `console_id` = ?";
    $stmt = $this->connection->prepare($query);

    return $stmt->execute([$consoleId]);
  }
}
