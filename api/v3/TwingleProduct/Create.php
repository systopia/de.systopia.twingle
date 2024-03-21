<?php

use Civi\Twingle\Shop\Exceptions\ProductException;
use CRM_Twingle_ExtensionUtil as E;
use Civi\Twingle\Shop\BAO\TwingleProduct;

/**
 * TwingleProduct.Create API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _civicrm_api3_twingle_product_Create_spec(&$spec) {
  $spec['id'] = [
    'name' => 'id',
    'title' => E::ts('TwingleProduct ID'),
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 0,
    'description' => E::ts('The TwingleProduct ID in the database'),
  ];
  $spec['external_id'] = [
    'name' => 'external_id',
    'title' => E::ts('Twingle ID'),
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
    'description' => E::ts('External product ID in Twingle database'),
  ];
  $spec['project_id'] = [
    'name' => 'project_id',
    'title' => E::ts('Twingle Shop ID'),
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
    'description' => E::ts('ID of the corresponding Twingle Shop'),
  ];
  $spec['name'] = [
    'name' => 'name',
    'title' => E::ts('Product Name'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'description' => E::ts('Name of the product'),
  ];
  $spec['is_active'] = [
    'name' => 'is_active',
    'title' => E::ts('Is active?'),
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.required' => 0,
    'api.default' => 1,
    'description' => E::ts('Is the product active?'),
  ];
  $spec['description'] = [
    'name' => 'description',
    'title' => E::ts('Product Description'),
    'type' => CRM_Utils_Type::T_TEXT,
    'api.required' => 0,
    'description' => E::ts('Short description of the product'),
  ];
  $spec['price'] = [
    'name' => 'price',
    'title' => E::ts('Product Price'),
    'type' => CRM_Utils_Type::T_FLOAT,
    'api.required' => 0,
    'description' => E::ts('Price of the product'),
  ];
  $spec['sort'] = [
    'name' => 'sort',
    'title' => E::ts('Sort'),
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 0,
    'description' => E::ts('Sort order of the product'),
  ];
  $spec['financial_type_id'] = [
    'name' => 'financial_type_id',
    'title' => E::ts('Financial Type ID'),
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
    'description' => E::ts('ID of the financial type of the product'),
  ];
  $spec['twingle_shop_id'] = [
    'name' => 'twingle_shop_id',
    'title' => E::ts('FK to TwingleShop'),
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
    'description' => E::ts('FK to TwingleShop'),
  ];
  $spec['tw_updated_at'] = [
    'name' => 'tw_updated_at',
    'title' => E::ts('Twingle timestamp'),
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
    'description' => E::ts('Timestamp of last update in Twingle db'),
  ];
  $spec['price_field_id'] = [
    'name' => 'price_field_id',
    'title' => E::ts('FK to PriceField'),
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 0,
    'description' => E::ts('FK to PriceField'),
  ];
}

/**
 * TwingleProduct.Create API
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @see civicrm_api3_create_success
 *
 * @throws API_Exception
 * @throws \Exception
 */
function civicrm_api3_twingle_product_Create($params): array {
  // Filter for allowed params
  $allowed_params = [];
  _civicrm_api3_twingle_product_Create_spec($allowed_params);
  $params = array_intersect_key($params, $allowed_params);

  try {
    // Create TwingleProduct and load params
    $product = new TwingleProduct();
    $product->load($params);

    // Save TwingleProduct
    $product->add();
    $result = $product->getAttributes();
    return civicrm_api3_create_success($result, $params, 'TwingleProduct', 'Create');
  }
  catch (ProductException $e) {
    return civicrm_api3_create_error($e->getMessage(), [
      'error_code' => $e->getCode(),
      'params' => $params,
    ]);
  }
}
