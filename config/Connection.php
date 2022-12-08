<?php

namespace Config;

use PDO;
use PDOException;

class Connection {
  private string $host;
  private string $dbName;
  private string $user;
  private string $password;

  private PDO $connection;

  /**
   * Создаёт и настраивает подключение к БД
   * @param string $host            сервер
   * @param string $dbName          название БД
   * @param string $user            пользователь
   * @param string $password        пароль
   */
  public function __construct(
    string $host = "localhost",
    string $dbName = "LsOnAdmin",
    string $user = "root",
    string $password = "alastor_cool"
  ) {
    $this->host = $host;
    $this->dbName = $dbName;
    $this->user = $user;
    $this->password = $password;

    try {
      $this->connection = new PDO(
        "mysql:host=$this->host;dbname=$this->dbName",
        $this->user,
        $this->password
      );
    }
    catch (PDOException $e) {
      echo "[ERROR]: {$e->getMessage()}" . PHP_EOL;
    }
  }

  /**
   * Возвращает подключение к БД
   * @return PDO
   */
  public function getConnection(): PDO {
    return $this->connection;
  }
}
