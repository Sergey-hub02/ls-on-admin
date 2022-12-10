const getErrorsHTML = errors => {
  if (errors.length === 0) {
    return "";
  }

  return errors.reduce((prev, current) => {
    return `${prev}\n<p class="text-danger mb-1">${current}</p>`;
  }, "");
}


const loginForm = document.querySelector("#login-form");
const messagebox = document.querySelector("#messagebox");

loginForm.addEventListener("submit", event => {
  event.preventDefault();

  const data = new FormData(loginForm);
  data.append(event.submitter.name, "");

  const xhr = new XMLHttpRequest();
  xhr.responseType = "json";

  xhr.open("POST", "/scripts/login.php");
  xhr.send(data);

  xhr.onload = () => {
    const response = xhr.response;

    if (xhr.status === 200 && response !== null) {
      // авторизация завершилась успешно
      messagebox.innerHTML = `<p class="text-success">${response['message']}</p>`;
      loginForm.reset();
      window.location = "/";
      return;
    }

    // авторизация завершилась с ошибками
    messagebox.innerHTML = getErrorsHTML(response["errors"]);
  }
});
