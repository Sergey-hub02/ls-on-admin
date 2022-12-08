<?php

namespace Api\Models;

require_once __DIR__ . "/Client.php";
require_once __DIR__ . "/Console.php";

class OrderConsole {
  private ?Console $console;
  private int $amount;

  /**
   * @param Console|null $console       заказываемая консоль
   * @param int $amount                 количество товара
   */
  public function __construct(
    ?Console $console = null,
    int $amount = 0
  ) {
    $this->console = $console;
    $this->amount = $amount;
  }

  /**
   * @return Console|null
   */
  public function getConsole(): ?Console {
    return $this->console;
  }

  /**
   * @param Console $console
   */
  public function setConsole(Console $console): void {
    $this->console = $console;
  }

  /**
   * @return int
   */
  public function getAmount(): int {
    return $this->amount;
  }

  /**
   * @param int $amount
   */
  public function setAmount(int $amount): void {
    $this->amount = $amount;
  }

  /**
   * Возвращает поля объекта в виде ассоциативного массива
   * @return array
   */
  public function toArray(): array {
    return [
      "console" => $this->getConsole()->toArray(),
      "amount" => $this->getAmount()
    ];
  }
}
