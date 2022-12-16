const toJson = data => {
  let json = {};

  data.forEach((value, key) => {
    json[key] = value;
  });

  return JSON.stringify(json);
}


const createDatabaseForm = document.querySelector("#create-database");
const messagebox = document.querySelector("#messagebox");

createDatabaseForm.addEventListener("submit", event => {
  event.preventDefault();

  const data = new FormData(createDatabaseForm);
  // data.append(event.submitter.name, "");

  const xhr = new XMLHttpRequest();
  xhr.responseType = "json";

  xhr.open("POST", "http://localhost/api/index.php/databases/create");
  xhr.send(toJson(data));

  xhr.onload = () => {
    const response = xhr.response;
    console.log(response);

    if (response && xhr.status === 201) {
      createDatabaseForm.reset();
      messagebox.innerHTML = `<p class="text-success">База данных успешно создана!</p>`;

      window.location.reload();
      return;
    }

    messagebox.innerHTML = `<p class="text-danger">${response['error']}</p>`;
  }
});
