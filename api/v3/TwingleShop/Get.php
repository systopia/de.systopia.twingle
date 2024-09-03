<?php
use CRM_Twingle_ExtensionUtil as E;
use Civi\Twingle\Shop\BAO\TwingleShop;

/**
 * TwingleShop.Get API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _civicrm_api3_twingle_shop_Get_spec(&$spec) {
  $spec['id'] = [
    'name' => 'id',
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
  $spec['name'] = [
    'name' => 'name',
    'title' => E::ts('Name'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => E::ts('Name of the TwingleShop'),
  ];
  $spec['price_set_id'] = [
    'name' => 'price_set_id',
    'title' => E::ts('Price Set ID'),
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 0,
    'description' => E::ts('FK to civicrm_price_set'),
  ];
}

/**
 * TwingleShop.Get API
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
function civicrm_api3_twingle_shop_Get($params) {
  // Filter for allowed params
  $allowed_params = [];
  _civicrm_api3_twingle_shop_Get_spec($allowed_params);
  $params = array_intersect_key($params, $allowed_params);

  // Build query
  $query = 'SELECT * FROM civicrm_twingle_shop';
  $query_params = [];

  if (!empty($params)) {
    $query = $query . ' WHERE';
    $possible_params = [];
    _civicrm_api3_twingle_shop_Get_spec($possible_params);
    $param_count = 1;

    foreach ($possible_params as $param) {
      if (!empty($params[$param['name']])) {
        $query = $query . ' ' . $param['name'] . " = %$param_count AND";
        $query_params[$param_count] = [
          $params[$param['name']],
          $param['type'] == CRM_Utils_Type::T_INT ? 'Integer' : 'String'
        ];
        $param_count++;
      }
    }
    // Cut away last 'AND'
    $query = substr($query, 0, -4);
  }

  // Execute query
  try {
    $dao = TwingleShop::executeQuery($query, $query_params);
  }
  catch (\Exception $e) {
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

  return civicrm_api3_create_success($returnValues, $params, 'TwingleShop', 'Get');
}
