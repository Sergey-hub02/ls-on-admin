<?php
session_start();

if (empty($_SESSION["user_id"])) {
  header("Location: /pages/login.php");
  die();
}

if (empty($_REQUEST["id"])) {
  die("[ERROR]: Не задан id базы данных!");
}

$databases = json_decode(
  file_get_contents("http://localhost/api/index.php/databases/list?user_id={$_SESSION['user_id']}"),
  true
);

$database = json_decode(
  file_get_contents("http://localhost/api/index.php/databases/list?id={$_REQUEST['id']}"),
  true
);

$tables = json_decode(
  file_get_contents("http://localhost/api/index.php/tables/list?database_id={$_REQUEST['id']}"),
  true
);
?>
<!DOCTYPE html>
<html lang="ru">

<head>
  <meta charset="UTF-8">
  <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">

  <title><?= $database['name'] ?></title>

  <link rel="stylesheet" href="/libs/bootstrap-5.2.3-dist/css/bootstrap.min.css">
</head>


<body>

<div class="d-flex" id="wrapper">
  <!-- Sidebar-->
  <div class="w-25 vh-100 border-end bg-white" id="sidebar-wrapper">
    <div class="p-3 border-bottom bg-light text-center">
      <h3 class="m-0">LS on Admin</h3>
    </div>

    <div class="list-group list-group-flush">
      <?php foreach ($databases as $db): ?>
        <a
          class="list-group-item list-group-item-action list-group-item-light p-3"
          href="/pages/database.php?id=<?= $db['database_id'] ?>"
        >
          <?= $db["name"] ?>
        </a>
      <?php endforeach; ?>

      <a
        class="list-group-item list-group-item-action list-group-item-light p-3"
        href="/pages/create_database.php"
      >
        Создать базу данных
      </a>
    </div>
  </div>

  <!-- Page content wrapper-->
  <div id="page-content-wrapper">
    <!-- Page content-->
    <div class="container-fluid">
      <h1 class="mt-4"><?= $database['name'] ?></h1>

      <div>
        <table class="table">
          <tr>
            <th scope="col">ID</th>
            <th scope="col">Название таблицы</th>
            <th scope="col">&nbsp;</th>
          </tr>

          <tbody id="columns">
            <?php foreach ($tables as $table): ?>
              <tr>
                <td>
                  <?= $table["table_id"] ?>
                </td>

                <td>
                  <a href="/pages/table.php?id=<?= $table['table_id'] ?>"><?= $table["name"] ?></a>
                </td>

                <td>
                  <form class="remove-table" action="/">
                    <input type="hidden" name="table_id" value="<?= $table['table_id'] ?>">
                    <input type="hidden" name="database-name" value="<?= $database['name'] ?>">
                    <input type="hidden" name="name" value="<?= $table['name'] ?>">

                    <button
                      type="submit"
                      class="btn btn-danger remove-table"
                      name="remove-table"
                    >
                      Удалить
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <a class="btn btn-primary" href="/pages/create_table.php?db=<?= $database['database_id'] ?>">Создать таблицу</a>
    </div>
  </div>
</div>

<script src="/assets/js/removeTable.js"></script>

</body>

</html>
