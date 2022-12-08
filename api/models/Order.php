<?php

namespace Api\Models;

require_once __DIR__ . "/Client.php";
require_once __DIR__ . "/OrderGamepad.php";
require_once __DIR__ . "/OrderConsole.php";

use Api\Models\User;
use Api\Models\OrderGamepad;
use Api\Models\OrderConsole;

class Order {
  private int $orderId;
  private ?User $client;
  private array $ordersGamepad;
  private array $ordersConsole;
  private float $price;

  /**
   * @param int $orderId                        id заказа
   * @param \Api\Models\User|null $client     клиент
   * @param array $ordersGamepad                заказы геймпадов
   * @param array $ordersConsole                заказы консолей
   * @param float $price                        стоимость заказа
   */
  public function __construct(
    int   $orderId = 0,
    ?User $client = null,
    array $ordersGamepad = [],
    array $ordersConsole = [],
    float $price = 0.0
  ) {
    $this->orderId = $orderId;
    $this->client = $client;
    $this->ordersGamepad = $ordersGamepad;
    $this->ordersConsole = $ordersConsole;
    $this->price = $price;
  }

  /**
   * @return int
   */
  public function getOrderId(): int {
    return $this->orderId;
  }

  /**
   * @param int $orderId
   */
  public function setOrderId(int $orderId): void {
    $this->orderId = $orderId;
  }

  /**
   * @return \Api\Models\User|null
   */
  public function getClient(): ?User {
    return $this->client;
  }

  /**
   * @param \Api\Models\User|null $client
   */
  public function setClient(?User $client): void {
    $this->client = $client;
  }

  /**
   * @return OrderGamepad[]
   */
  public function getOrdersGamepad(): array {
    return $this->ordersGamepad;
  }

  /**
   * @param array $ordersGamepad
   */
  public function setOrdersGamepad(array $ordersGamepad): void {
    $this->ordersGamepad = $ordersGamepad;
  }

  /**
   * @return OrderConsole[]
   */
  public function getOrdersConsole(): array {
    return $this->ordersConsole;
  }

  /**
   * @param array $ordersConsole
   */
  public function setOrdersConsole(array $ordersConsole): void {
    $this->ordersConsole = $ordersConsole;
  }

  /**
   * @return float
   */
  public function getPrice(): float {
    return $this->price;
  }

  /**
   * @param float $price
   */
  public function setPrice(float $price): void {
    $this->price = $price;
  }

  /**
   * Добавляет заказ консоли
   * @param \Api\Models\OrderConsole $orderConsole
   * @return void
   */
  public function addOrderConsole(OrderConsole $orderConsole): void {
    $this->ordersConsole[] = $orderConsole;
  }

  /**
   * Добавляет заказ геймпада
   * @param \Api\Models\OrderGamepad $orderGamepad
   * @return void
   */
  public function addOrderGamepad(OrderGamepad $orderGamepad): void {
    $this->ordersGamepad[] = $orderGamepad;
  }

  /**
   * Возвращает поля объекта в виде ассоциативного массива
   * @return array
   */
  public function toArray(): array {
    return [
      "order_id" => $this->getOrderId(),
      "client" => $this->getClient()->toArray(),
      "orders_gamepad" => array_map(
        function (OrderGamepad $order) {
          return $order->toArray();
        },
        $this->getOrdersGamepad()
      ),
      "orders_console" => array_map(
        function (OrderConsole $order) {
          return $order->toArray();
        },
        $this->getOrdersConsole()
      ),
      "price" => $this->getPrice()
    ];
  }
}
