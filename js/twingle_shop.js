/**
 * This file contains the JavaScript code for the Twingle Shop integration.
 */

/**
 * This function initializes the Twingle Shop integration.
 */
function twingleShopInit() {
  cj('#twingle-shop-spinner').hide();

  // Run once on page load
  load_financial_types();
  twingle_shop_active_changed();
  twingle_map_products_changed();
  twingle_fetch_products();

  // Add event listeners
  cj('#enable_shop_integration:checkbox').change(twingle_shop_active_changed);
  cj('#shop_map_products:checkbox').change(twingle_map_products_changed);
  cj('#btn_fetch_products').click(function (event) {
    event.preventDefault(); // Prevent the default form submission behavior
    twingle_fetch_products();
  });
}

// Define financial types as global variable
let financialTypes = {};

/**
 * Load financial types from CiviCRM
 */
function load_financial_types() {
  CRM.api3('FinancialType', 'get', {
    'sequential': 1,
    'options': { 'limit': 0 },
  }).then(function (result) {
    financialTypes = result.values.reduce((obj, item) => {
      obj[item.id] = item.name;
      return obj;
    }, {});
  });
}

/**
 * Fetches the Twingle products for the given project identifiers.
 */
function twingle_fetch_products() {
  let active = cj('#shop_map_products:checkbox:checked').length;
  if (active) {
    cj('#twingle-shop-spinner').show();
    CRM.api3('TwingleShop', 'fetch', {
      'project_identifiers': cj('#selectors :input').val(),
    }).then(function (result) {
      if (result.is_error === 1) {
        cj('#btn_fetch_products').crmError(result.error_message, ts('Could not fetch products', []));
        cj('#twingle-shop-spinner').hide();
        return;
      }
      buildShopTables(result);
      cj('#twingle-shop-spinner').hide();
    }, function () {
      cj('#btn_fetch_products').crmError(ts('Could not fetch products. Please check your Twingle API key.', []));
      cj('#twingle-shop-spinner').hide();
    });
  }
}

/**
 * Update the form fields based on whether shop integration is currently active
 */
function twingle_shop_active_changed() {
  let active = cj('#enable_shop_integration:checkbox:checked').length;
  if (active) {
    cj('.twingle-shop-element').show();
  } else {
    cj('.twingle-shop-element').hide();
  }
}

/**
 * Display fetch button and product mapping when the corresponding option is active
 */
function twingle_map_products_changed() {
  let active = cj('#shop_map_products:checkbox:checked').length;
  if (active) {
    cj('.twingle-product-mapping').show();
  } else {
    cj('.twingle-product-mapping').hide();
  }
}

/**
 * This function builds the shop tables.
 * @param shopData
 */
function buildShopTables(shopData) {

  let productTables = [];

  // Create table for each project (shop)
  for (const key in shopData.values) {
    productTables.push(new ProductsTable(shopData.values[key]));
  }

  // Add table container to DOM
  const tableContainer = document.getElementById('tableContainer');

  // Add tables to table container
  for (const productTable of productTables) {
    tableContainer.appendChild(productTable.table);
  }
}

/**
 * Get the value of the default financial type for the shops defined in this profile.
 * @returns {string|string}
 */
function getShopDefaultFinancialType() {
  const default_selection = document.getElementById('s2id_shop_financial_type');
  const selected = default_selection.getElementsByClassName('select2-chosen')[0];
  return selected ? selected.textContent : '';
}

/**
 * Get the value of the default financial type.
 * @returns {string}
 */
function getShopDefaultFinancialTypeValue() {
  const shopDefaultFinancialType = getShopDefaultFinancialType();
  return Object.keys(financialTypes).find(key => financialTypes[key] === shopDefaultFinancialType);
}

/**
 * This class represents a Twingle Product.
 */
class Product {

  /**
   * Creates a new Product object.
   * @param productData
   * @param parentTable
   */
  constructor(productData, parentTable) {
    this.parentTable = parentTable;
    this.setProps(productData);
  }

  /**
   * Sets the properties of this product.
   * @param productData
   * @private
   */
  setProps(productData) {
    this.id = productData.id;
    this.name = productData.name;
    this.isActive = productData.is_active;
    this.price = productData.price;
    this.sort = productData.sort;
    this.description = productData.description;
    this.projectId = productData.project_id;
    this.externalId = productData.external_id;
    this.isOutdated = productData.is_outdated;
    this.isOrphaned = productData.is_orphaned;
    // this.updatedAt = productData.updated_at;
    this.createdAt = productData.created_at;
    this.twUpdatedAt = productData.tw_updated_at;
    this.financialTypeId = productData.financial_type_id;
    this.priceFieldId = productData.price_field_id;
  }

  /**
   * Dumps the product data.
   * @returns {{id, name, is_active, price, sort, description, project_id, external_id, financial_type_id, tw_updated_at, twingle_shop_id: *}}
   */
  dumpData() {
    return {
      'id': this.id,
      'name': this.name,
      'is_active': this.isActive,
      'price': this.price,
      'sort': this.sort,
      'description': this.description,
      'project_id': this.projectId,
      'external_id': this.externalId,
      'financial_type_id': this.financialTypeId,
      'price_field_id': this.priceFieldId,
      'tw_updated_at': this.twUpdatedAt,
      'twingle_shop_id': this.parentTable.id,
    };
  }

  /**
   * Creates a button for creating, updating or deleting the price field for
   * this product.
   * @param action
   * @param handler
   * @returns {HTMLButtonElement}
   * @private
   */
  createProductButton(action, handler) {
    // Create button
    const button = document.createElement('button');
    button.id = action + '_twingle_product_tw_' + this.externalId;
    button.classList.add('twingle-shop-cell-button');

    // Add button text
    let text = action === 'create' ? ts('Create', []) : action === 'update' ? ts('Update', []) : ts('Delete', []);
    button.textContent = ' ' + ts(text, []);

    // Add button handler
    if (handler) {
      button.onclick = handler;
    } else {
      button.disabled = true;
    }

    // Deactivate 'create' button if product hast no financial type
    if (action === 'create' && this.financialTypeId === null) {
      button.disabled = true;
    }

    // Deactivate 'update' button if product is not outdated
    if (action === 'update' && !this.isOutdated) {
      button.disabled = true;
    }

    // Add icon
    const icon = document.createElement('i');
    const iconClass = action === 'create' ? 'fa-plus-circle' : action === 'update' ? 'fa-refresh' : 'fa-trash';
    icon.classList.add('crm-i', iconClass);
    button.insertBefore(icon, button.firstChild);

    return button;
  }

  /**
   * Creates a handler for creating a price field for this product.
   * @returns {(function(*): void)|*}
   * @private
   */
  createPriceFieldHandler() {
    const self = this;
    return function (event) {
      event.preventDefault();
      const action = event.target.innerText.includes('Update') ? 'updated' : 'created';
      CRM.api3('TwingleProduct', 'create', self.dumpData())
        .then(function (result) {
          if (result.is_error === 1) {
            cj('#create_twingle_product_tw_' + self.id).crmError(result.error_message, ts('Could not create Price Field for this product', []));
          } else {
            self.update(result.values);
            CRM.alert(ts(`The Price Field was ${action} successfully.`, []), ts(`Price Field ${action}`, []), 'success', {'expires': 5000});
          }
        }, function (error) {
          cj('#create_twingle_product_tw_' + self.id).crmError(error.message, ts('Could not create Price Field for this product', []));
        });
    };
  }

  /**
   * Creates a handler for creating a price field for this product.
   * @returns {(function(*): void)|*}
   * @private
   */
  deletePriceFieldHandler() {
    let self = this;
    return function (event) {
      event.preventDefault();
      const options = {
        'title': ts('Delete Price Field', []),
        'message': ts('Are you sure you want to delete the price field associated with this product?', []),
      };
      CRM.confirm(options)
        .on('crmConfirm:yes', function () {
          CRM.api3('TwingleProduct', 'delete', { 'id': self.id })
            .then(function (result) {
              if (result.is_error === 1) {
                cj('#create_twingle_product_tw_' + self.id).crmError(result.error_message, ts('Could not delete Price Field', []));
              } else {
                self.update();
              }
              CRM.alert(ts('The Price Field was deleted successfully.', []), ts('Price Field deleted', []), 'success', {'expires': 5000});
            }, function (error) {
              cj('#create_twingle_product_tw_' + self.id).crmError(error.message, ts('Could not delete Price Field', []));
            });
        });
    };
  }

  /**
   * Creates a new row with the product name and buttons for creating, updating
   * or deleting the price field for this product.
   * @returns {*}
   */
  createRow() {
    let row;

    // Clear row
    if (this.row) {
      for (let i = this.row.cells.length - 1; i >= 0; i--) {
        // Delete everything from row
        this.row.deleteCell(i);
      }
      row = this.row;
    } else {
      // Create new row element
      row = document.createElement('tr');

      // Add id to row
      row.id = 'twingle_product_tw_' + this.externalId;
    }

    // Add cell with product name
    const nameCell = document.createElement('td');
    if (this.isOrphaned) {
      nameCell.classList.add('strikethrough');
    }
    nameCell.textContent = this.name;
    row.appendChild(nameCell);

    // Add cell for buttons
    let buttonCell = row.insertCell(1);

    // Add product buttons which allow to create, update or delete the price
    // field for this product
    if (this.parentTable.id) {
      let buttons = this.createProductButtons();
      for (const button of buttons) {
        buttonCell.appendChild(button);
      }
    }

    // Add financial type dropdown for each product if price set exists
    if (this.parentTable.id) {
      let dropdown = this.createFinancialTypeDropdown();
      const cell = document.createElement('td');
      cell.classList.add('twingle-shop-financial-type-select');
      cell.appendChild(dropdown);
      row.insertCell(2).appendChild(cell);
    }
    // else add default financial type
    else {
      const cell = document.createElement('td');
      cell.classList.add('twingle-shop-financial-type-default');
      cell.innerHTML = '<i>' + getShopDefaultFinancialType() + '</i>';
      row.insertCell(1).appendChild(cell);
    }

    this.row = row;
    return this.row;
  }

  /**
   * Determining which actions are available for this product and creating a
   * button for each of them.
   * @returns {Array} Array of buttons
   */
  createProductButtons() {
    let actionsAndHandlers = [];
    let buttons = [];

    // Determine actions; if product has price field id, it can be updated or
    // deleted, otherwise it can be created
    if (this.priceFieldId) {
      if (!this.isOrphaned) {
        actionsAndHandlers.push(['update', this.createPriceFieldHandler()]);
      }
      actionsAndHandlers.push(['delete', this.deletePriceFieldHandler()]);
    } else if (!this.isOrphaned) {
      actionsAndHandlers.push(['create', this.createPriceFieldHandler()]);
    }

    // Create button for each action
    for (const [action, handler] of actionsAndHandlers) {
      buttons.push(this.createProductButton(action, handler));
    }

    return buttons;
  }

  /**
   * Creates a dropdown for selecting the financial type for this product.
   * @returns {HTMLSelectElement}
   * @private
   */
  createFinancialTypeDropdown() {
    // Create new dropdown element
    const dropdown = document.createElement('select');
    dropdown.id = 'twingle_product_tw_' + this.externalId + '_financial_type';

    // Add empty option if no price field exists
    if (!this.priceFieldId) {
      let option = document.createElement('option');
      option.value = '';
      option.innerHTML = '&lt;' + ts('select financial type', []) + '&gt;';
      option.selected = true;
      option.disabled = true;
      dropdown.appendChild(option);
    }

    // Add options for each financial type available in CiviCRM
    for (const key in financialTypes) {
      let option = document.createElement('option');
      option.value = key;
      option.text = financialTypes[key]; // financialTypes is defined in twingle_shop.tpl as smarty variable
      if (this.financialTypeId !== null && this.financialTypeId.toString() === key) {
        option.selected = true;
      }
      dropdown.appendChild(option);
    }

    // Add handlers
    let self = this;
    dropdown.onchange = function () {

      //  Enable 'create' or 'update' button if financial type is selected
      const button = document.getElementById('twingle_product_tw_' + self.externalId).getElementsByClassName('twingle-shop-cell-button')[0];
      if (button.textContent.includes('Create') || button.textContent.includes('Update')) {
        button.disabled = dropdown.value === '0';
      }

      // Update financial type
      self.financialTypeId = dropdown.value;
    };

    return dropdown;
  }

  /**
   * Updates the product properties and rebuilds the row.
   * @param productData
   */
  update(productData = null) {
    if (productData) {
      this.setProps(productData);
    } else {
      this.reset();
    }
    this.createRow();
  }

  /**
   * Resets the product properties.
   */
  reset() {
    this.financialTypeId = null;
    this.priceFieldId = null;
    this.isOutdated = null;
    this.isOutdated = null;
    // this.updatedAt = null;
    this.createdAt = null;
    this.id = null;
  }

}

/**
 * This class represents a Twingle Shop.
 */
class ProductsTable {

  /**
   * Creates a new ProductsTable object.
   * @param projectData
   */
  constructor(projectData) {
    this.setProps(projectData);
  }

  /**
   * Sets the properties of this project.
   * @param projectData
   * @private
   */
  setProps(projectData) {
    this.id = projectData.id;
    this.name = projectData.name;
    this.numericalProjectId = projectData.numerical_project_id;
    this.projectIdentifier = projectData.project_identifier;
    this.products = projectData.products.map(productData => new Product(productData, this));
    this.priceSetId = projectData.price_set_id;
    this.table = this.buildTable();
  }

  /**
   * Dumps the projects data.
   * @returns {{price_set_id, financial_type_id, numerical_project_id, name, id, project_identifier, products: *}}
   */
  dumpData() {
    return {
      'id': this.id,
      'name': this.name,
      'numerical_project_id': this.numericalProjectId,
      'project_identifier': this.projectIdentifier,
      'price_set_id': this.priceSetId,
      'products': this.products.map(product => product.dumpData()),
      'financial_type_id': getShopDefaultFinancialTypeValue()
    };
  }

  /**
   * Builds the table for this project (shop).
   * @returns {HTMLTableElement}
   * @private
   */
  buildTable() {
    let table;

    // Clear table body
    if (this.table) {
      this.clearTableHeader();
      this.clearTableBody();
      this.updateTableButtons();
      table = this.table;
    } else {
      // Create new table element
      table = document.createElement('table');
      table.classList.add('twingle-shop-table');
      table.id = this.projectIdentifier;

      // Add caption
      const caption = table.createCaption();
      caption.textContent = this.name + ' (' + this.projectIdentifier + ')';
      caption.classList.add('twingle-shop-table-caption');

      // Add table body
      const tbody = document.createElement('tbody');
      table.appendChild(tbody);

      // Add table buttons
      this.addTableButtons(table);
    }

    // Add header row
    const thead = table.createTHead();
    const headerRow = thead.insertRow();
    const headers = [ts('Product', []), ts('Financial Type', [])];

    // Add price field column if price set exists
    if (this.priceSetId) {
      headers.splice(1, 0, ts('Price Field', []));
    }

    for (const headerText of headers) {
      const headerCell = document.createElement('th');
      headerCell.textContent = headerText;
      headerRow.appendChild(headerCell);
    }

    // Add products to table
    this.addProductsToTable(table);

    return table;
  }

  /**
   * Adds buttons for creating, updating or deleting the price set for the
   * given project (shop).
   * @private
   */
  addTableButtons(table) {
    table.appendChild(this.createTableButton('update', this.updatePriceSetHandler()));
    if (this.priceSetId === null) {
      table.appendChild(this.createTableButton('create', this.createPriceSetHandler()));
    } else {
      table.appendChild(this.createTableButton('delete', this.deletePriceSetHandler()));
    }
  }

  /**
   * Creates a button for creating, updating or deleting the price set for the
   * given project (shop).
   * @param action
   * @param handler
   * @returns {HTMLButtonElement}
   * @private
   */
  createTableButton(action, handler) {
    // Create button
    const button = document.createElement('button');
    button.id = 'btn_' + action + '_twingle_shop_' + this.projectIdentifier;
    button.classList.add('crm-button', 'twingle-shop-table-button');

    // Add button text
    const text = action === 'create' ? ts('Create Price Set', []) : action === 'update' ? ts('Update Price Set', []) : ts('Delete Price Set', []);
    button.textContent = ' ' + ts(text, []);

    // Add button handler
    button.onclick = handler;

    // Add icon
    const icon = document.createElement('i');
    const iconClass = action === 'create' ? 'fa-plus-circle' : action === 'update' ? 'fa-refresh' : 'fa-trash';
    icon.classList.add('crm-i', iconClass);
    button.insertBefore(icon, button.firstChild);

    return button;
  }

  /**
   * Adds products to table body.
   * @param table
   * @private
   */
  addProductsToTable(table) {
    // Get table body
    const tbody = table.getElementsByTagName('tbody')[0];

    // Add products to table body
    for (const product of this.products) {
      // Add row for product
      const row = product.createRow();
      // Add row to table
      tbody.appendChild(row);
    }
  }

  /**
   * Updates the table buttons.
   */
  updateTableButtons() {
    const table_buttons = this.table.getElementsByClassName('twingle-shop-table-button');
    // Remove all price set buttons from table
    while (table_buttons.length > 0) {
      table_buttons[0].remove();
    }
    this.addTableButtons(this.table);
  }

  /**
   * Clears the table header.
   * @private
   */
  clearTableHeader() {
    const thead = this.table.getElementsByTagName('thead')[0];
    while (thead.firstChild) {
      thead.removeChild(thead.firstChild);
    }
  }

  /**
   * Clears the table body.
   */
  clearTableBody() {
    const tbody = this.table.getElementsByTagName('tbody')[0];
    while (tbody.firstChild) {
      tbody.removeChild(tbody.firstChild);
    }
  }

  /**
   * Creates a handler for creating the price set for the given project (shop).
   * @returns {(function(*): void)|*}
   */
  createPriceSetHandler() {
    let self = this;
    return function (event) {
      event.preventDefault();
      CRM.api3('TwingleShop', 'create', self.dumpData())
        .then(function (result) {
          if (result.is_error === 1) {
            cj('#btn_create_price_set_' + self.projectIdentifier).crmError(result.error_message, ts('Could not create Twingle Shop', []));
          } else {
            self.update();
            CRM.alert(ts('The Price Set was created successfully.', []), ts('Price Field created', []), 'success', {'expires': 5000});
          }
        }, function (error) {
          cj('#btn_create_price_set_' + self.projectIdentifier).crmError(error.message, ts('Could not create TwingleShop', []));
        });
    };
  }

  /**
   * Creates a handler for deleting the price set for the given project (shop).
   * @returns {(function(*): void)|*}
   */
  deletePriceSetHandler() {
    let self = this;
    return function (event) {
      event.preventDefault();
      const options = {
        'title': ts('Delete Price Set', []),
        'message': ts('Are you sure you want to delete the price set associated with this Twingle Shop?', []),
      };
      CRM.confirm(options)
        .on('crmConfirm:yes', function () {
          CRM.api3('TwingleShop', 'delete', {
            'project_identifier': self.projectIdentifier,
          }).then(function (result) {
            if (result.is_error === 1) {
              cj('#btn_create_price_set_' + self.projectIdentifier).crmError(result.error_message, ts('Could not delete Twingle Shop', []));
            } else {
              self.update();
              CRM.alert(ts('The Price Set was deleted successfully.', []), ts('Price Set deleted', []), 'success', {'expires': 5000});
            }
          }, function (error) {
            cj('#btn_delete_price_set_' + self.projectIdentifier).crmError(error.message, ts('Could not delete Twingle Shop', []));
          });
        });
    };
  }

  /**
   * Creates a handler for updating the price set for the given project (shop).
   * @returns {(function(*): void)|*}
   */
  updatePriceSetHandler() {
    let self = this;
    return function (event) {
      cj('#twingle-shop-spinner').show();
      if (event) {
        event.preventDefault();
      }
      CRM.api3('TwingleShop', 'fetch', {
        'project_identifiers': self.projectIdentifier,
      }).then(function (result) {
        if (result.is_error === 1) {
          cj('#btn_create_price_set_' + self.projectIdentifier).crmError(result.error_message, ts('Could not delete Twingle Shop', []));
          cj('#twingle-shop-spinner').hide();
        } else {
          self.update(result.values[self.projectIdentifier]);
          cj('#twingle-shop-spinner').hide();
        }
      }, function (error) {
        cj('#btn_update_price_set_' + self.projectIdentifier).crmError(error.message, ts('Could not update Twingle Shop', []));
        cj('#twingle-shop-spinner').hide();
      });
    };
  }

  /**
   * Updates the project properties and rebuilds the table.
   * @param projectData
   */
  update(projectData) {
    if (!projectData) {
      const updatePriceSet = this.updatePriceSetHandler();
      updatePriceSet();
    } else {
      this.setProps(projectData);
      this.buildTable();
    }
  }
}
