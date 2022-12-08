<?php

namespace Api\Models;

require_once __DIR__ . "/Gamepad.php";

class WiredGamepad extends Gamepad {
  private float $cabelLength;
  private float $consumption;

  /**
   * @param int $gamepadId          ID геймпада
   * @param string $name            название геймпада
   * @param string $brand           компания, выпустившая геймпад
   * @param int $buttons            количество кнопок
   * @param float $price            цена геймпада
   * @param string $image           путь к изображению
   * @param float $cabelLength      длина кабеля
   * @param float $consumption      потребление энергии
   */
  public function __construct(
    int $gamepadId = 0,
    string $name = "",
    string $brand = "",
    int $buttons = 0,
    float $price = 0.0,
    string $image = "",
    float $cabelLength = 0.0,
    float $consumption = 0.0
  ) {
    parent::__construct($gamepadId, $name, $brand, $buttons, $price, $image);
    $this->cabelLength = $cabelLength;
    $this->consumption = $consumption;
  }

  /**
   * @return float
   */
  public function getCabelLength(): float {
    return $this->cabelLength;
  }

  /**
   * @param float $cabelLength
   */
  public function setCabelLength(float $cabelLength): void {
    $this->cabelLength = $cabelLength;
  }

  /**
   * @return float
   */
  public function getConsumption(): float {
    return $this->consumption;
  }

  /**
   * @param float $consumption
   */
  public function setConsumption(float $consumption): void {
    $this->consumption = $consumption;
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
      "cabel_length" => $this->getCabelLength(),
      "consumption" => $this->getConsumption()
    ];
  }
}
