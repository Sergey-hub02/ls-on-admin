const createTableForm = document.querySelector("#create-table");
const messagebox = document.querySelector("#messagebox");

const addRow = document.querySelector("#add-row");
const removeRow = document.querySelector("#remove-row");
const columns = document.querySelector("#columns");

addRow.addEventListener("click", function () {
  const tr = document.createElement("tr");
  tr.className = "column";

  tr.innerHTML = `
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
  `;

  columns.appendChild(tr);
});

removeRow.addEventListener("click", () => {
  const columns = document.querySelectorAll(".column");
  const length = columns.length;

  if (length === 1)
    return;

  columns[length - 1].remove();
});

createTableForm.addEventListener("submit", event => {
  event.preventDefault();

  const data = new FormData(createTableForm);
  data.append(event.submitter.name, "");

  const xhr = new XMLHttpRequest();
  xhr.responseType = "json";

  xhr.open("POST", "/scripts/createTable.php");
  xhr.send(data);

  xhr.onload = () => {
    const response = xhr.response;

    if (response && xhr.status === 201) {
      window.location.reload();
    }
  }
});
