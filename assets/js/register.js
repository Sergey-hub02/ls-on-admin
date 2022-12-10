const getErrorsHTML = errors => {
  if (errors.length === 0) {
    return "";
  }

  return errors.reduce((prev, current) => {
    return `${prev}\n<p class="text-danger mb-1">${current}</p>`;
  }, "");
}


const registerForm = document.querySelector("#register-form");
const messagebox = document.querySelector("#messagebox");

registerForm.addEventListener("submit", event => {
  event.preventDefault();

  const data = new FormData(registerForm);
  data.append(event.submitter.name, "");

  const xhr = new XMLHttpRequest();
  xhr.responseType = "json";

  xhr.open("POST", "/scripts/register.php");
  xhr.send(data);

  xhr.onload = () => {
    const response = xhr.response;

    if (xhr.status === 201 && response !== null) {
      // регистрация прошла успешно
      registerForm.reset();
      messagebox.innerHTML = `
        <p class="text-success">Вы успешно зарегистрировались!</p>
        <p class="text-success">Перейти на <a href="/">главную</a> страницу</p>
      `;
      return;
    }

    // регистрация завершилась с ошибками
    messagebox.innerHTML = getErrorsHTML(response["errors"]);
  }
});
