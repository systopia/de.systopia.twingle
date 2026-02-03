<?php

declare(strict_types = 1);

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
require_once 'twingle.civix.php';
// phpcs:enable

use CRM_Twingle_ExtensionUtil as E;

/**
 * Implements hook_civicrm_pre().
 *
 * @throws \Civi\Twingle\Shop\Exceptions\ProductException
 * @throws \CRM_Core_Exception
 * @throws \Civi\Twingle\Shop\Exceptions\ShopException
 */
function twingle_civicrm_pre($op, $objectName, $id, &$params) {
  if ($objectName === 'ContributionRecur' && $op === 'edit') {
    CRM_Twingle_Tools::checkRecurringContributionChange((int) $id, $params);
  }

  // Create/delete PriceField and PriceFieldValue for TwingleProduct
  elseif ($objectName === 'TwingleProduct') {
    $twingle_product = new CRM_Twingle_BAO_TwingleProduct();
    $twingle_product->load($params);
    if ($op === 'create' || $op === 'edit') {
      $twingle_product->createPriceField();
    }
    elseif ($op === 'delete') {
      $twingle_product->deletePriceField();
    }
    $params = $twingle_product->getAttributes();
  }

  // Create PriceSet for TwingleShop
  elseif ($objectName === 'TwingleShop' && ($op === 'create' || $op === 'edit')) {
    $twingle_shop = new CRM_Twingle_BAO_TwingleShop();
    $twingle_shop->load($params);
    $twingle_shop->createPriceSet();
    $params = $twingle_shop->getAttributes();
  }
}

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function twingle_civicrm_config(&$config) {
  _twingle_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function twingle_civicrm_install() {
  _twingle_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function twingle_civicrm_enable() {
  _twingle_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_permission().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_permission
 */
function twingle_civicrm_permission(&$permissions) {
  $permissions['access Twingle API'] = [
    'label' => E::ts('Twingle API: Access Twingle API'),
    'description' => E::ts('Allows access to the Twingle API actions.'),
  ];
}

/**
 * Implements hook_civicrm_alterAPIPermissions().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterAPIPermissions
 */
function twingle_civicrm_alterAPIPermissions($entity, $action, &$params, &$permissions) {
  // Restrict API calls to the permission.
  $permissions['twingle_donation']['submit'] = ['access Twingle API'];
  $permissions['twingle_donation']['cancel']  = ['access Twingle API'];
  $permissions['twingle_donation']['endrecurring']  = ['access Twingle API'];
}

/**
 * Make sure, that the last_access and access_counter column is not logged
 *
 * @param array $logTableSpec
 */
function twingle_civicrm_alterLogTables(&$logTableSpec) {
  if (isset($logTableSpec['civicrm_twingle_profile'])) {
    $logTableSpec['civicrm_twingle_profile']['exceptions'] = ['last_access', 'access_counter'];
  }
}
