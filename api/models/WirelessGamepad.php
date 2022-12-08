<?php

namespace Api\Models;

require_once __DIR__ . "/Gamepad.php";

class WirelessGamepad extends Gamepad {
  private float $capacity;
  private float $frequency;

  /**
   * @param int $gamepadId        ID геймпада
   * @param string $name          название геймпада
   * @param string $brand         компания, выпустившая геймпад
   * @param int $buttons          количество кнопок
   * @param float $price          цена геймпада
   * @param string $image         путь к изображению
   * @param float $capacity       ёмкость аккумулятора
   * @param float $frequency      частота
   */
  public function __construct(
    int $gamepadId = 0,
    string $name = "",
    string $brand = "",
    int $buttons = 0,
    float $price = 0.0,
    string $image = "",
    float $capacity = 0.0,
    float $frequency = 0.0
  ) {
    parent::__construct($gamepadId, $name, $brand, $buttons, $price, $image);
    $this->capacity = $capacity;
    $this->frequency = $frequency;
  }

  /**
   * @return float
   */
  public function getCapacity(): float {
    return $this->capacity;
  }

  /**
   * @param float $capacity
   */
  public function setCapacity(float $capacity): void {
    $this->capacity = $capacity;
  }

  /**
   * @return float
   */
  public function getFrequency(): float {
    return $this->frequency;
  }

  /**
   * @param float $frequency
   */
  public function setFrequency(float $frequency): void {
    $this->frequency = $frequency;
  }

  /**
   * Возвращает поля объекта в виде ассоциативного массива
   * @return array
   */
  public function toArray(): array {
    return [
      "gamepad_id" => $this->getGamepadId(),
      "name" => $this->getName(),
      "brand" => $this->getBrand(),
      "buttons" => $this->getButtons(),
      "price" => $this->getPrice(),
      "image" => $this->getImage(),
      "capacity" => $this->getCapacity(),
      "frequency" => $this->getFrequency()
    ];
  }
}
