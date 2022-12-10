<?php

header("Access-Control-Allow-Origin: http://localhost:8080");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once __DIR__ . "/controllers/UserController.php";
require_once __DIR__ . "/controllers/DatabaseController.php";

use Api\Controllers\UserController;
use Api\Controllers\DatabaseController;

$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$uri = explode("/", $uri);

// доступные группы конечных точек
$sections = [
  "users",
  "databases",
];

$section = $uri[3];

if (isset($section) && !in_array($section, $sections) || !isset($uri[4])) {
  header("HTTP/1.1 404 Not Found");
  die();
}

$controller = null;

switch ($section) {
  case "users":
    $controller = new UserController();
    break;

  case "databases":
    $controller = new DatabaseController();
    break;

  default:
    break;
}

$methodName = $uri[4] . "Action";
$controller->{$methodName}();
