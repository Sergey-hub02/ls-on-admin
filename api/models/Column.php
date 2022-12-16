<?php

namespace Api\Models;

class Column {
  private string $name;
  private string $type;
  private bool $null;
  private bool $primary;
  private bool $autoIncrement;

  /**
   * @param string $name            название столбца
   * @param string $type            тип данных
   * @param bool $null              NULL или NOT NULL
   * @param bool $primary           является ли первичным ключом
   * @param bool $autoIncrement     AUTO_INCREMENT
   */
  public function __construct(
    string $name = "",
    string $type = "",
    bool $null = false,
    bool $primary = false,
    bool $autoIncrement = false
  ) {
    $this->name = $name;
    $this->type = $type;
    $this->null = $null;
    $this->primary = $primary;
    $this->autoIncrement = $autoIncrement;
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
  public function getType(): string {
    return $this->type;
  }

  /**
   * @param string $type
   */
  public function setType(string $type): void {
    $this->type = $type;
  }

  /**
   * @return bool
   */
  public function isNull(): bool {
    return $this->null;
  }

  /**
   * @param bool $null
   */
  public function setNull(bool $null): void {
    $this->null = $null;
  }

  /**
   * @return bool
   */
  public function isPrimary(): bool {
    return $this->primary;
  }

  /**
   * @param bool $primary
   */
  public function setPrimary(bool $primary): void {
    $this->primary = $primary;
  }

  /**
   * @return bool
   */
  public function isAutoIncrement(): bool {
    return $this->autoIncrement;
  }

  /**
   * @param bool $autoIncrement
   */
  public function setAutoIncrement(bool $autoIncrement): void {
    $this->autoIncrement = $autoIncrement;
  }

  /**
   * Возвращает поля объекта в виде ассоциативного массива
   * @return array
   */
  public function toArray(): array {
    return [
      "name" => $this->getName(),
      "type" => $this->getType(),
      "null" => $this->isNull(),
      "primary" => $this->isPrimary(),
      "auto_increment" => $this->isAutoIncrement(),
    ];
  }
}
