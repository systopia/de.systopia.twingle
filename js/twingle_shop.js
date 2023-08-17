
function buildProductsTable(jsonData) {

  // Clear table first
  clearProductsTable();

  const tableContainer = document.getElementById('tableContainer');

  for (const key in jsonData.values) {
    if (jsonData.values.hasOwnProperty(key)) {
      const project = jsonData.values[key];

      if (project.hasOwnProperty('products')) {
        const products = project.products;

        const divider = document.createElement('hr');
        divider.classList.add('twingle-shop-table-divider');
        tableContainer.appendChild(divider);

        const table = document.createElement('table');
        tableContainer.appendChild(table);

        const caption = table.createCaption();
        caption.textContent = 'Twingle Shop: ' + key;
        caption.classList.add('twingle-shop-table-caption');

        const thead = table.createTHead();
        const headerRow = thead.insertRow();

        const headers = [ts('Products'), ts('State')];
        for (const headerText of headers) {
          const headerCell = document.createElement('th');
          headerCell.textContent = headerText;
          headerRow.appendChild(headerCell);
        }

        const tbody = document.createElement('tbody');
        table.appendChild(tbody);

        for (const productId in products) {
          const product = products[productId];

          const row = tbody.insertRow();
          const nameCell = row.insertCell(0);
          const stateCell = row.insertCell(1);

          nameCell.textContent = product.name;
          stateCell.textContent = get_price_field_state(product.id);
        }

        // Add button to create price set
        const create_price_set_button = document.createElement('button');
        create_price_set_button.textContent = ts('Create Price Set');
        create_price_set_button.id = 'twingle_shop_' + key;
        create_price_set_button.onclick = createPriceSetHandler(key);
        tableContainer.appendChild(create_price_set_button);
      }
    }
  }
}

function clearProductsTable() {
  const tableContainer = document.getElementById('tableContainer');
  while (tableContainer.firstChild) {
    tableContainer.removeChild(tableContainer.firstChild);
  }
}

function createPriceSetHandler(key) {
  return function () {
    event.preventDefault(); // Prevent the default form submission behavior
    create_price_set(key);
  };
}

function create_price_set(key) {
  alert("Create Price Set for table: " + key);
}

function get_price_field_state(id) {
  return (id);
}
