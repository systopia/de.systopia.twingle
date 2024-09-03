<?php

use CRM_Twingle_ExtensionUtil as E;
use Civi\Twingle\Shop\BAO\TwingleProduct;

/**
 * TwingleProduct.Get API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _civicrm_api3_twingle_product_Get_spec(&$spec) {
  $spec['id'] = [
    'name' => 'id',
    'title' => E::ts('TwingleProduct ID'),
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 0,
    'description' => E::ts('The TwingleProduct ID in CiviCRM'),
  ];
  $spec['external_id'] = [
    'name' => 'external_id',
    'title' => E::ts('External TwingleProduct ID'),
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 0,
    'description' => E::ts('Twingle\'s ID of the product'),
  ];
  $spec['price_field_id'] = [
    'name' => 'Price Field ID',
    'title' => E::ts('Price Field ID'),
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 0,
    'description' => E::ts('FK to civicrm_price_field'),
  ];
  $spec['twingle_shop_id'] = [
    'name' => 'twingle_shop_id',
    'title' => E::ts('TwingleShop ID'),
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 0,
    'description' => E::ts('The TwingleShop ID in CiviCRM'),
  ];
  $spec['project_identifier'] = [
    'name' => 'project_identifier',
    'title' => E::ts('Project Identifier'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => E::ts('Twingle project identifier'),
  ];
  $spec['numerical_project_id'] = [
    'name' => 'numerical_project_id',
    'title' => E::ts('Numerical Project Identifier'),
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 0,
    'description' => E::ts('Twingle numerical project identifier'),
  ];
}

/**
 * TwingleProduct.Get API
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @throws API_Exception
 * @see civicrm_api3_create_success
 *
 */
function civicrm_api3_twingle_product_Get($params) {
  // Filter for allowed params
  $allowed_params = [];
  _civicrm_api3_twingle_product_Get_spec($allowed_params);
  $params = array_intersect_key($params, $allowed_params);

  // Build query
  $query = 'SELECT ctp.* FROM civicrm_twingle_product ctp
  INNER JOIN civicrm_twingle_shop cts ON ctp.twingle_shop_id = cts.id';
  $query_params = [];

  if (!empty($params)) {
    $query = $query . ' WHERE';
    $possible_params = [];
    _civicrm_api3_twingle_product_Get_spec($possible_params);
    $param_count = 1;
    $altered_params = [];

    // Specify product fields to define table prefix
    $productFields = array_keys(TwingleProduct::fields());

    // Alter params (prefix with table name)
    foreach ($possible_params as $param) {
      if (!empty($params[$param['name']])) {
        // Prefix with table name
        $table_prefix = in_array($param['name'], $productFields) ? 'ctp.' : 'cts.';
        $altered_params[] = [
          'name' => $table_prefix . $param['name'],
          'value' => $params[$param['name']],
          'type' => $param['type'],
        ];
      }
    }

    // Add altered params to query
    foreach ($altered_params as $param) {
      $query = $query . ' ' . $param['name'] . " = %$param_count AND";
      $query_params[$param_count] = [
        $param['value'],
        $param['type'] == CRM_Utils_Type::T_INT ? 'Integer' : 'String',
      ];
      $param_count++;
    }
  }

  // Cut away last 'AND'
  $query = substr($query, 0, -4);

  // Execute query
  try {
    $dao = TwingleProduct::executeQuery($query, $query_params);
  }
  catch (Exception $e) {
    return civicrm_api3_create_error($e->getMessage(), [
      'error_code' => $e->getCode(),
      'params' => $params,
    ]);
  }

  // Prepare return values
  $returnValues = [];
  while ($dao->fetch()) {
    $returnValues[] = $dao->toArray();
  }

  return civicrm_api3_create_success($returnValues, $params, 'TwingleProduct', 'Get');
}
