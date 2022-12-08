<?php

namespace Api\Models;

class Console {
  private int $consoleId;
  private string $name;
  private string $brand;
  private string $gpu;
  private string $cpu;
  private int $ram;
  private float $price;
  private string $image;

  /**
   * @param int $consoleId        ID консоли
   * @param string $name          название консоли
   * @param string $brand         компания, выпустившая консоль
   * @param string $gpu           название видеокарты
   * @param string $cpu           название процессора
   * @param int $ram              объём оперативной памяти
   * @param float $price          цена консоли
   * @param string $image         путь к изображению
   */
  public function __construct(
    int $consoleId = 0,
    string $name = "",
    string $brand = "",
    string $gpu = "",
    string $cpu = "",
    int $ram = 0,
    float $price = 0.0,
    string $image = ""
  ) {
    $this->consoleId = $consoleId;
    $this->name = $name;
    $this->brand = $brand;
    $this->gpu = $gpu;
    $this->cpu = $cpu;
    $this->ram = $ram;
    $this->price = $price;
    $this->image = $image;
  }

  /**
   * @return int
   */
  public function getConsoleId(): int {
    return $this->consoleId;
  }

  /**
   * @param int $consoleId
   */
  public function setConsoleId(int $consoleId): void {
    $this->consoleId = $consoleId;
  }

  /**
   * @return string
   */
  public function getName(): string {
    return $this->name;
  }

  /**
   * @param string $name
   */
  public function setName(string $name): void {
    $this->name = $name;
  }

  /**
   * @return string
   */
  public function getBrand(): string {
    return $this->brand;
  }

  /**
   * @param string $brand
   */
  public function setBrand(string $brand): void {
    $this->brand = $brand;
  }

  /**
   * @return string
   */
  public function getGpu(): string {
    return $this->gpu;
  }

  /**
   * @param string $gpu
   */
  public function setGpu(string $gpu): void {
    $this->gpu = $gpu;
  }

  /**
   * @return string
   */
  public function getCpu(): string {
    return $this->cpu;
  }

  /**
   * @param string $cpu
   */
  public function setCpu(string $cpu): void {
    $this->cpu = $cpu;
  }

  /**
   * @return int
   */
  public function getRam(): int {
    return $this->ram;
  }

  /**
   * @param int $ram
   */
  public function setRam(int $ram): void {
    $this->ram = $ram;
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
   * @return string
   */
  public function getImage(): string {
    return $this->image;
  }

  /**
   * @param string $image
   */
  public function setImage(string $image): void {
    $this->image = $image;
  }

  /**
   * Вовзращает поля объекта в виде ассоциативного массива
   * @return array
   */
  public function toArray(): array {
    return [
      "console_id" => $this->getConsoleId(),
      "name" => $this->getName(),
      "brand" => $this->getBrand(),
      "gpu" => $this->getGpu(),
      "cpu" => $this->getCpu(),
      "ram" => $this->getRam(),
      "price" => $this->getPrice(),
      "image" => $this->getImage()
    ];
  }
}
