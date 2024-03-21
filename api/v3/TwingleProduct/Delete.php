<?php

use CRM_Twingle_ExtensionUtil as E;
use Civi\Twingle\Shop\BAO\TwingleProduct;

/**
 * TwingleProduct.Delete API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _civicrm_api3_twingle_product_Delete_spec(&$spec) {
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
}

/**
 * TwingleProduct.Delete API
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @throws API_Exception*@throws \Exception
 * @throws \Exception
 * @see civicrm_api3_create_success
 *
 */
function civicrm_api3_twingle_product_Delete($params) {
  // Filter for allowed params
  $allowed_params = [];
  _civicrm_api3_twingle_product_Delete_spec($allowed_params);
  $params = array_intersect_key($params, $allowed_params);

  // Find TwingleProduct via getsingle API
  $product_data = civicrm_api3('TwingleProduct', 'getsingle', $params);
  if ($product_data['is_error']) {
    return civicrm_api3_create_error($product_data['error_message'],
      ['error_code' => $product_data['error_code'], 'params' => $params]
    );
  }

  // Get TwingleProduct object
  $product = TwingleProduct::findById($product_data['id']);

  // Delete TwingleProduct and associated PriceField and PriceFieldValue
  $result = $product->delete();
  if ($result) {
    return civicrm_api3_create_success(1, $params, 'TwingleProduct', 'Delete');
  }
  else {
    return civicrm_api3_create_error(
      E::ts('TwingleProduct could not be deleted.'),
      ['error_code' => 'delete_failed', 'params' => $params]
    );
  }
}
