<?php

namespace Api\Models;

class Role {
  private int $roleId;
  private string $title;
  private string $description;

  /**
   * @param int $roleId                id роли
   * @param string $title              название роли
   * @param string $description        описание роли
   */
  public function __construct(
    int $roleId = 0,
    string $title = "",
    string $description = "",
  ) {
    $this->roleId = $roleId;
    $this->title = $title;
    $this->description = $description;
  }

  /**
   * @return int
   */
  public function getRoleId(): int {
    return $this->roleId;
  }

  /**
   * @param int $roleId
   */
  public function setRoleId(int $roleId): void {
    $this->roleId = $roleId;
  }

  /**
   * @return string
   */
  public function getTitle(): string {
    return $this->title;
  }

  /**
   * @param string $title
   */
  public function setTitle(string $title): void {
    $this->title = $title;
  }

  /**
   * @return string
   */
  public function getDescription(): string {
    return $this->description;
  }

  /**
   * @param string $description
   */
  public function setDescription(string $description): void {
    $this->description = $description;
  }

  /**
   * Возвращает поля объекта в виде ассоциативного массива
   * @return array
   */
  public function toArray(): array {
    return [
      "role_id" => $this->getRoleId(),
      "title" => $this->getTitle(),
      "description" => $this->getDescription(),
    ];
  }
}
