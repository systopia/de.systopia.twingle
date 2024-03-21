<?php
use CRM_Twingle_ExtensionUtil as E;

/**
 * TwingleProduct.Getsingle API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _civicrm_api3_twingle_product_Getsingle_spec(&$spec) {
  _civicrm_api3_twingle_product_Get_spec($spec);
}

/**
 * TwingleProduct.Getsingle API
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
function civicrm_api3_twingle_product_Getsingle($params) {
  // Filter for allowed params
  $allowed_params = [];
  _civicrm_api3_twingle_product_Getsingle_spec($allowed_params);
  $params = array_intersect_key($params, $allowed_params);

  // Check whether any parameters are set
  if (empty($params)) {
    return civicrm_api3_create_error(
      "At least one parameter must be set",
      ['error_code' => 'missing_parameter', 'params' => $params]
    );
  }

  // Find TwingleProduct via get API
  $returnValues = civicrm_api3('TwingleProduct', 'get', $params);
  $count = $returnValues['count'];

  // Check whether only a single TwingleProduct is found
  if ($count != 1) {
    return civicrm_api3_create_error(
      "Expected one TwingleProduct but found $count",
      ['error_code' => 'not_found', 'params' => $params]
    );
  }
  return $returnValues['values'][$returnValues['id']];
}
