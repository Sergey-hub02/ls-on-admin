<?php

namespace Config;

use mysqli;
use mysqli_sql_exception;

class DBConnection {
  private string $host;
  private string $user;
  private string $password;

  private mysqli $connection;

  /**
   * Создаёт и настраивает подключение к БД
   * @param string $host            сервер
   * @param string $user            пользователь
   * @param string $password        пароль
   */
  public function __construct(
    string $host = "localhost",
    string $user = "root",
    string $password = "alastor_cool"
  ) {
    $this->host = $host;
    $this->user = $user;
    $this->password = $password;

    try {
      $this->connection = new mysqli($this->host, $this->user, $this->password);
    }
    catch (mysqli_sql_exception $e) {
      echo "[ERROR]: {$e->getMessage()}" . PHP_EOL;
    }
  }

  /**
   * Возвращает подключение к БД
   * @return mysqli
   */
  public function getConnection(): mysqli {
    return $this->connection;
  }
}