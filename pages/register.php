<!DOCTYPE html>
<html lang="ru">

<head>
  <meta charset="UTF-8">
  <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Регистрация</title>
  <link rel="stylesheet" href="/libs/bootstrap-5.2.3-dist/css/bootstrap.min.css">
</head>


<body>

<div class="vh-100 d-flex justify-content-center align-items-center">
  <form id="register-form" action="/" method="post">
    <h2 class="text-center mb-4">Регистрация</h2>

    <div class="form-outline mb-4">
      <label class="form-label" for="username">Логин</label>

      <input
        type="text"
        id="username"
        name="username"
        class="form-control"
        required
      >
    </div>

    <div class="form-outline mb-4">
      <label class="form-label" for="email">Email</label>

      <input
        type="email"
        id="email"
        name="email"
        class="form-control"
        required
      >
    </div>

    <div class="form-outline mb-4">
      <label class="form-label" for="password">Пароль</label>

      <input
        type="password"
        id="password"
        name="password"
        class="form-control"
        required
      >
    </div>

    <input type="hidden" name="role_id" value="1">

    <button
      type="submit"
      name="register"
      class="btn btn-secondary btn-block mb-4 w-100"
    >
      Зарегистрироваться
    </button>

    <div class="text-center">
      <p>Уже есть учётная запись? <a href="/pages/login.php">Авторизуйтесь</a></p>
    </div>

    <div id="messagebox"></div>
  </form>
</div>

<script src="/libs/bootstrap-5.2.3-dist/js/bootstrap.min.js"></script>
<script src="/assets/js/register.js"></script>

</body>

</html>
