<?php

namespace Api\Models;

require_once __DIR__ . "/Role.php";

use Api\Models;
use Api\Models\Role;

class User {
  private int $userId;
  private string $username;
  private string $email;
  private string $password;
  private ?Role $role;

  /**
   * @param int $userId                 ID пользователя
   * @param string $username            имя пользователя
   * @param string $email               фамилия пользователя
   * @param string $password            email пользователя
   * @param Role|null $role             роль пользователя
   */
  public function __construct(
    int $userId = 0,
    string $username = "",
    string $email = "",
    string $password = "",
    ?Role $role = null
  ) {
    $this->userId = $userId;
    $this->username = $username;
    $this->email = $email;
    $this->password = $password;
    $this->role = $role;
  }

  /**
   * @return int
   */
  public function getUserId(): int {
    return $this->userId;
  }

  /**
   * @param int $userId
   */
  public function setUserId(int $userId): void {
    $this->userId = $userId;
  }

  /**
   * @return string
   */
  public function getUsername(): string {
    return $this->username;
  }

  /**
   * @param string $username
   */
  public function setUsername(string $username): void {
    $this->username = $username;
  }

  /**
   * @return string
   */
  public function getEmail(): string {
    return $this->email;
  }

  /**
   * @param string $email
   */
  public function setEmail(string $email): void {
    $this->email = $email;
  }

  /**
   * @return string
   */
  public function getPassword(): string {
    return $this->password;
  }

  /**
   * @param string $password
   */
  public function setPassword(string $password): void {
    $this->password = $password;
  }

  /**
   * @return Role|null
   */
  public function getRole(): ?Role {
    return $this->role;
  }

  /**
   * @param Role|null $role
   */
  public function setRole(?Role $role): void {
    $this->role = $role;
  }

  /**
   * Возвращает поля объекта в виде ассоциативного массива
   * @return array
   */
  public function toArray(): array {
    $fields = [
      "user_id" => $this->getUserId(),
      "username" => $this->getUsername(),
      "email" => $this->getEmail(),
      "role" => $this->getRole()->toArray(),
    ];

    if (empty($this->password))
      return $fields;

    $fields["password"] = $this->getPassword();
    return $fields;
  }
}
