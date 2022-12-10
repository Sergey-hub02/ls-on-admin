<?php

namespace Api\Models;

require_once __DIR__ . "/User.php";

class Database {
  private int $databaseId;
  private string $name;
  private ?User $user;

  /**
   * @param int $databaseId         id базы данных
   * @param string $name            название базы данных
   * @param User|null $user         пользователь, создавший базу данных
   */
  public function __construct(
    int $databaseId = 0,
    string $name = "",
    ?User $user = null
  ) {
    $this->databaseId = $databaseId;
    $this->name = $name;
    $this->user = $user;
  }

  /**
   * @return int
   */
  public function getDatabaseId(): int {
    return $this->databaseId;
  }

  /**
   * @param int $databaseId
   */
  public function setDatabaseId(int $databaseId): void {
    $this->databaseId = $databaseId;
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
   * @return User|null
   */
  public function getUser(): ?User {
    return $this->user;
  }

  /**
   * @param User|null $user
   */
  public function setUser(?User $user): void {
    $this->user = $user;
  }

  public function toArray(): array {
    return [
      "database_id" => $this->getDatabaseId(),
      "name" => $this->getName(),
      "user" => $this->getUser()->toArray()
    ];
  }
}