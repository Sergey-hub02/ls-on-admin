<?php

namespace Api\Models;

require_once __DIR__ . "/Column.php";

class Table {
  private int $tableId;
  private string $name;
  private int $databaseId;
  private array $columns;

  /**
   * @param int $tableId            id таблицы
   * @param string $name            название таблицы
   * @param int $databaseId         id базы данных
   * @param Column[] $columns       столбцы таблицы
   */
  public function __construct(
    int $tableId = 0,
    string $name = "",
    int $databaseId = 0,
    array $columns = []
  ) {
    $this->tableId = $tableId;
    $this->name = $name;
    $this->databaseId = $databaseId;
    $this->columns = $columns;
  }

  /**
   * @return int
   */
  public function getTableId(): int {
    return $this->tableId;
  }

  /**
   * @param int $tableId
   */
  public function setTableId(int $tableId): void {
    $this->tableId = $tableId;
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
   * @return Column[]
   */
  public function getColumns(): array {
    return $this->columns;
  }

  /**
   * @param Column[] $columns
   */
  public function setColumns(array $columns): void {
    $this->columns = $columns;
  }

  /**
   * Добавляет столбец в таблицу
   * @param Column $column
   * @return void
   */
  public function addColumn(Column $column): void {
    $this->columns[] = $column;
  }

  /**
   * Возвращает поля объекта в виде ассоциативного массива
   * @return array
   */
  public function toArray(): array {
    return [
      "table_id" => $this->getTableId(),
      "name" => $this->getName(),
      "database_id" => $this->getDatabaseId(),
      "columns" => array_map(function (Column $column) {
        return $column->toArray();
      }, $this->getColumns()),
    ];
  }
}
