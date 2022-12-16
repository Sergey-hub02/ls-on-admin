<?php
session_start();

if (empty($_SESSION["user_id"])) {
  header("Location: /pages/login.php");
  die();
}

if (empty($_REQUEST["db"])) {
  die("[ERROR]: Не задан id базы данных!");
}

$dbId = intval($_REQUEST["db"]);

$databases = json_decode(
  file_get_contents("http://localhost/api/index.php/databases/list?user_id={$_SESSION['user_id']}"),
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

  <title>Создание базы данных</title>

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
      <h1 class="mt-4">Создание таблицы</h1>

      <div>
        <form id="create-table" action="/" method="post">
          <div class="form-group mb-3">
            <label for="table-name">Название таблицы</label>
            <input
              type="text"
              class="form-control"
              name="table-name"
              id="table-name"
              required
            >
          </div>

          <input
            type="hidden"
            name="database_id"
            value="<?= $dbId ?>"
          >

          <div class="form-group mb-3">
            <table class="table">
              <tr>
                <th scope="col">Название столбца</th>
                <th scope="col">Тип данных</th>
                <th scope="col">Null</th>
                <th scope="col">Первичный ключ</th>
                <th scope="col">AUTO_INCREMENT</th>
              </tr>

              <tbody id="columns" >
              <tr class="column">
                <td>
                  <input
                    type="text"
                    name="name[]"
                  >
                </td>

                <td>
                  <select name="type[]">
                    <option value="INT">INT</option>
                    <option value="VARCHAR(255)">VARCHAR</option>
                    <option value="TEXT">TEXT</option>
                    <option value="DECIMAL">DECIMAL</option>
                  </select>
                </td>

                <td>
                  <input
                    type="checkbox"
                    name="null[]"
                    value="Y"
                  >
                  <input
                    type="hidden"
                    name="null[]"
                    value="N"
                  >
                </td>

                <td>
                  <input
                    type="checkbox"
                    name="primary[]"
                    value="Y"
                  >
                  <input
                    type="hidden"
                    name="primary[]"
                    value="N"
                  >
                </td>

                <td>
                  <input
                    type="checkbox"
                    name="auto_increment[]"
                    value="Y"
                  >
                  <input
                    type="hidden"
                    name="auto_increment[]"
                    value="N"
                  >
                </td>
              </tr>
              </tbody>
            </table>
          </div>

          <button type="button" id="add-row" class="btn btn-secondary">Добавить строку</button>
          <button type="button" id="remove-row" class="btn btn-danger">Удалить строку</button>
          <button type="submit" name="create-table" class="btn btn-primary">Создать</button>

          <div class="mt-3" id="messagebox"></div>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="/assets/js/create_table.js"></script>

</body>

</html>
