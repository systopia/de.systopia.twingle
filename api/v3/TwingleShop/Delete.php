<?php
use CRM_Twingle_ExtensionUtil as E;
use Civi\Twingle\Shop\BAO\TwingleShop;

/**
 * TwingleShop.Delete API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _civicrm_api3_twingle_shop_Delete_spec(&$spec) {
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
}

/**
 * TwingleShop.Delete API
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @throws \API_Exception
 * @throws \Civi\Twingle\Shop\Exceptions\ShopException
 * @throws \Exception
 * @see civicrm_api3_create_success
 *
 */
function civicrm_api3_twingle_shop_Delete($params) {
  // Filter for allowed params
  $allowed_params = [];
  _civicrm_api3_twingle_shop_Delete_spec($allowed_params);
  $params = array_intersect_key($params, $allowed_params);

  // Find TwingleShop via getsingle API
  $shop_data = civicrm_api3('TwingleShop', 'getsingle', $params);
  if ($shop_data['is_error']) {
    return civicrm_api3_create_error($shop_data['error_message'],
      ['error_code' => $shop_data['error_code'], 'params' => $params]
    );
  }

  // Get TwingleShop object
  $shop = TwingleShop::findById($shop_data['id']);

  // Delete TwingleShop
  /* @var \Civi\Twingle\Shop\BAO\TwingleShop $shop */
  $result = $shop->deleteByConstraint();
  if ($result) {
    return civicrm_api3_create_success(1, $params, 'TwingleShop', 'Delete');
  }
  elseif ($result === 0) {
    return civicrm_api3_create_error(
      E::ts('TwingleShop could not be found.'),
      ['error_code' => 'not_found', 'params' => $params]
    );
  }
  else {
    return civicrm_api3_create_error(
      E::ts('TwingleShop could not be deleted.'),
      ['error_code' => 'delete_failed', 'params' => $params]
    );
  }
}
