<?php
use CRM_Twingle_ExtensionUtil as E;
use Civi\Twingle\Shop\BAO\TwingleShop;
use Civi\Twingle\Shop\Exceptions\ShopException;

/**
 * TwingleShop.Create API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _civicrm_api3_twingle_shop_Create_spec(&$spec) {
  $spec['project_identifier'] = [
    'name' => 'project_identifier',
    'title' => E::ts('Project Identifier'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'description' => E::ts('Twingle project identifier'),
  ];
  $spec['numerical_project_id'] = [
    'name' => 'numerical_project_id',
    'title' => E::ts('Numerical Project Identifier'),
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
    'description' => E::ts('Numerical Twingle project identifier'),
  ];
  $spec['name'] = [
    'name' => 'name',
    'title' => E::ts('Shop Name'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'description' => E::ts('Name of the shop'),
  ];
  $spec['financial_type_id'] = [
    'name' => 'financial_type_id',
    'title' => E::ts('Financial Type ID'),
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
    'description' => E::ts('FK to civicrm_financial_type'),
  ];
}

/**
 * TwingleShop.Create API
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @see civicrm_api3_create_success
 *
 * @throws API_Exception
 */
function civicrm_api3_twingle_shop_Create($params) {
  // Filter for allowed params
  $allowed_params = [];
  _civicrm_api3_twingle_shop_Create_spec($allowed_params);
  $params = array_intersect_key($params, $allowed_params);

  try {
    // Create TwingleShop and load params
    $shop = new TwingleShop();
    $shop->load($params);

    // Save TwingleShop
    $result = $shop->add();

    // Return success
    return civicrm_api3_create_success($result, $params, 'TwingleShop', 'Create');
  }
  catch (ShopException $e) {
    return civicrm_api3_create_error($e->getMessage(), [
      'error_code' => $e->getErrorCode(),
      'params' => $params,
    ]);
  }
}
