<?php
use CRM_Twingle_ExtensionUtil as E;
use CRM_Twingle_Shop_DAO_TwingleShop as TwingleShop;
use CRM_Twingle_Exceptions_Shop_ShopException as TwingleShopException;
use CRM_Twingle_Exceptions_Shop_ApiCallError as TwingleApiCallError;
use CRM_Twingle_Exceptions_Shop_ProductException as TwingleShopProductException;

/**
 * TwingleShop.Fetch API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _civicrm_api3_twingle_shop_Fetch_spec(&$spec) {
  $spec['project_identifiers'] = [
    'name' => 'project_identifiers',
    'title' => E::ts('Project Identifiers'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'description' => E::ts('Comma separated list of Twingle project identifiers.'),
  ];
}

/**
 * TwingleShop.Fetch API
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
function civicrm_api3_twingle_shop_Fetch($params) {
  // Filter for allowed params
  $allowed_params = [];
  _civicrm_api3_twingle_shop_Fetch_spec($allowed_params);
  $params = array_intersect_key($params, $allowed_params);

  $returnValues = [];

  // Explode string with project IDs
  $projectIds = array_map(
    fn($projectId) => trim($projectId),
    explode(',', $params['project_identifiers'])
  );

  // Get products for all projects of type 'shop'
  foreach ($projectIds as $projectId) {
    try {
      $shop = new TwingleShop($projectId);
      $returnValues[$projectId]['products'] = $shop->fetchProducts();
    }
    catch (TwingleShopException|TwingleApiCallError|TwingleShopProductException $e) {
      // If this project identifier doesn't belong to a project of type
      // 'shop', just skip it
      if ($e->getErrorCode() == TwingleShopException::ERROR_CODE_NOT_A_SHOP) {
        $returnValues[$projectId] = "project is not of type 'shop'";
        continue;
      }
      // Else, log error and throw exception
      else {
        Civi::log()->error(
          $e->getMessage(),
          [
            'project_identifier' => $projectId,
            'params' => $params,
          ]
        );
        return civicrm_api3_create_error($e->getMessage(), [
          'error_code' => $e->getErrorCode(),
          'project_identifier' => $projectId,
          'params' => $params,
        ]);
      }
    }
  }

  return civicrm_api3_create_success(
    $returnValues,
    $params,
    'TwingleShop',
    'Fetch'
  );
}
