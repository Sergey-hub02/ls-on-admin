<?php
session_start();

if (empty($_SESSION["user_id"])) {
  header("Location: /pages/login.php");
  die();
}

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

  <title>Главная</title>

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
          class="list-group-item list-group-item-action list-group-item-light p-3 d-flex justify-content-between"
          href="/pages/database.php?id=<?= $db['database_id'] ?>"
        >
          <?= $db["name"] ?>

          <form class="remove-database" action="/">
            <input type="hidden" name="database-id" value="<?= $db['database_id'] ?>">
            <input type="hidden" name="database-name" value="<?= $db['name'] ?>">

            <button
              type="submit"
              class="btn btn-danger remove-db"
              name="remove-db"
            >
              Удалить
            </button>
          </form>
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
      <h1 class="mt-4">LS on Admin</h1>
      <p>Используя данные интернет-ресурс, вы можете создавать свои базы данных и работать с ними.</p>
    </div>
  </div>
</div>

<script src="/assets/js/removeDatabase.js"></script>

</body>

</html>
