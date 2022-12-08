<?php

namespace Api\Models;

require_once __DIR__ . "/Gamepad.php";
require_once __DIR__ . "/Client.php";

class OrderGamepad {
  private ?Gamepad $gamepad;
  private int $amount;

  /**
   * @param Gamepad|null $gamepad       заказываемый геймпад
   * @param int $amount                 количество товара
   */
  public function __construct(
    ?Gamepad $gamepad = null,
    int $amount = 0
  ) {
    $this->gamepad = $gamepad;
    $this->amount = $amount;
  }

  /**
   * @return Gamepad|null
   */
  public function getGamepad(): ?Gamepad {
    return $this->gamepad;
  }

  /**
   * @param Gamepad $gamepad
   */
  public function setGamepad(Gamepad $gamepad): void{
    $this->gamepad = $gamepad;
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
      "gamepad" => $this->getGamepad()->toArray(),
      "amount" => $this->getAmount()
    ];
  }
}
