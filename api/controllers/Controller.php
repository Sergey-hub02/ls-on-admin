<?php

namespace Api\Controllers;

class Controller {
  /**
   * Вызывается каждый раз, когда происходит попытка вызвать несуществующий метод
   * @param string $name
   * @param array $arguments
   * @return void
   */
  public function __call(string $name, array $arguments): void {
    $this->sendOutput("", ["HTTP/1.1 404 Not Found"]);
  }

  /**
   * Возвращает элементы URI
   * @return array
   */
  public function getUriSegments(): array {
    $uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
    return explode("/", $uri);
  }

  /**
   * Возвращает параметры запроса
   * @return mixed
   */
  public function getQueryStringParams(): array {
    if (empty($_SERVER["QUERY_STRING"])) {
      return [];
    }

    parse_str($_SERVER["QUERY_STRING"], $query);
    return $query;
  }

  /**
   * Посылает ответ от сервера
   * @param mixed $data           посылаемые данные
   * @param array $headers        HTTP-заголовки
   */
  public function sendOutput(mixed $data, array $headers = []): void {
    header_remove("Set-Cookie");

    if (is_array($headers) && count($headers)) {
      foreach ($headers as $header) {
        header($header);
      }
    }

    echo $data;
    die();
  }
}
