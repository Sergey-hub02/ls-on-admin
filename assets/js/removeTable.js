const removeTable = document.querySelectorAll(".remove-table");

removeTable.forEach(btn => {
  btn.addEventListener("submit", event => {
    event.preventDefault();

    if (!confirm("Вы уверены, что хотите удалить таблицу?")) {
      return;
    }

    const data = new FormData(event.target);
    data.append(event.submitter.name, "");

    const xhr = new XMLHttpRequest();

    xhr.responseType = "json";
    xhr.open("POST", "/scripts/deleteTable.php");

    xhr.send(data);
    xhr.onload = () => {
      const response = xhr.response;

      if (response && xhr.status === 200) {
        window.location.reload();
        return;
      }

      alert("Ошибка при удалении таблицы!");
    }
  });
});
