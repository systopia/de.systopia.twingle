<?php
/*------------------------------------------------------------+
| SYSTOPIA Twingle Integration                                |
| Copyright (C) 2019 SYSTOPIA                                 |
| Author: B. Endres (endres@systopia.de)                      |
+-------------------------------------------------------------+
| This program is released as free software under the         |
| Affero GPL license. You can redistribute it and/or          |
| modify it under the terms of this license which you         |
| can read by viewing the included agpl.txt or online         |
| at www.gnu.org/licenses/agpl.html. Removal of this          |
| copyright header is strictly prohibited without             |
| written permission from the original author(s).             |
+-------------------------------------------------------------*/

use CRM_Twingle_ExtensionUtil as E;

class CRM_Twingle_Tools {

  /**
   * This flag can be used to temporarily suspend twingle protection
   * @var bool
   */
  public static $protection_suspended = FALSE;

  /**
   * Check if the attempted modification of the recurring contribution is allowed.
   * If not, an exception will be raised
   *
   * @param $recurring_contribution_id int
   * @param $change                    array
   * @throws Exception if the change is not allowed
   */
  public static function checkRecurringContributionChange($recurring_contribution_id, $change) {
    // check if a change to the status is planned
    if (empty($change['contribution_status_id'])) return;

    // check if the target status is not closed
    if (in_array($change['contribution_status_id'], [2,5])) return;

    // check if we're suspended
    if (self::$protection_suspended) return;

    // currently only works with prefixes
    $prefix = CRM_Core_BAO_Setting::getItem('de.systopia.twingle', 'twingle_prefix');
    if (empty($prefix)) return;

    // check if protection is turned on
    $protection_on = CRM_Core_BAO_Setting::getItem('de.systopia.twingle', 'twingle_protect_recurring');
    if (empty($protection_on)) return;

    // load the recurring contribution
    $recurring_contribution = civicrm_api3('ContributionRecur', 'getsingle', [
        'return' => 'trxn_id,contribution_status_id,payment_instrument_id',
        'id'     => $recurring_contribution_id]);

    // check if this is a SEPA transaction
    if (self::isSDD($recurring_contribution['payment_instrument_id'])) return;

    // check if it's really a termination (i.e. current status is 2 or 5)
    if (!in_array($recurring_contribution['contribution_status_id'], [2,5])) return;

    // check if it's a Twingle contribution
    if (substr($recurring_contribution['trxn_id'], 0, strlen($prefix)) == $prefix) {
      // this is a Twingle contribution that is about to be terminated
      throw new Exception(E::ts("This is a Twingle recurring contribution. It should be terminated through the Twingle interface, otherwise it will still be collected."));
    }
  }

  /**
   * Check if the given payment instrument is SEPA
   *
   * @param $payment_instrument_id string payment instrument
   * @return boolean
   */
  public static function isSDD($payment_instrument_id) {
    static $sepa_payment_instruments = NULL;
    if ($sepa_payment_instruments === NULL) {
      // init with instrument names
      $sepa_payment_instruments = ['FRST', 'RCUR', 'OOFF'];

      // lookup and add instrument IDs
      $lookup = civicrm_api3('OptionValue', 'get', [
          'option_group_id' => 'payment_instrument',
          'name'            => ['IN' => $sepa_payment_instruments],
          'return'          => 'value'
      ]);
      foreach ($lookup['values'] as $payment_instrument) {
        $sepa_payment_instruments[] = $payment_instrument['value'];
      }
    }
    return in_array($payment_instrument_id, $sepa_payment_instruments);
  }

  /**
   * Get a CiviSEPA mandate for the given contribution ID
   *
   * @param $contribution_id integer contribution ID *or* recurring contribution ID
   * @return integer mandate ID or null
   */
  public static function getMandateFor($contribution_id) {
    $contribution_id = (int) $contribution_id;
    if ($contribution_id) {
      try {
        // try recurring mandate
        $rcur_mandate = civicrm_api3('SepaMandate', 'get', [
            'entity_id'    => $contribution_id,
            'entity_table' => 'civicrm_contribution_recur',
            'type'         => 'RCUR',
        ]);
        if ($rcur_mandate['count'] == 1) {
          return reset($rcur_mandate['values']);
        }

        // try OOFF mandate
        // try recurring mandate
        $ooff_mandate = civicrm_api3('SepaMandate', 'get', [
            'entity_id'    => $contribution_id,
            'entity_table' => 'civicrm_contribution',
            'type'         => 'OOFF',
        ]);
        if ($ooff_mandate['count'] == 1) {
          return reset($ooff_mandate['values']);
        }
      } catch (Exception $ex) {
        Civi::log()->error("CRM_Twingle_Tools::getMandate failde for [{$contribution_id}]: " . $ex->getMessage());
      }
    }
    return NULL;
  }
}
