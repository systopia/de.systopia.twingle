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

declare(strict_types = 1);

use CRM_Twingle_ExtensionUtil as E;
use Civi\Twingle\Exceptions\BaseException;

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
   * @param int $recurring_contribution_id
   * @param array<mixed> $change
   * @throws Exception if the change is not allowed
   */
  public static function checkRecurringContributionChange(int $recurring_contribution_id, array $change): void {
    // check if a change to the status is planned
    if (empty($change['contribution_status_id'])) {
      return;
    }

    // check if the target status is not closed
    if (in_array($change['contribution_status_id'], [2, 5])) {
      return;
    }

    // check if we're suspended
    if (self::$protection_suspended) {
      return;
    }

    // check if protection is turned on
    $protection_on = Civi::settings()->get('twingle_protect_recurring');
    if (empty($protection_on)) {
      return;
    }

    // load the recurring contribution
    $recurring_contribution = civicrm_api3('ContributionRecur', 'getsingle', [
      'return' => 'trxn_id,contribution_status_id,payment_instrument_id,contact_id',
      'id'     => $recurring_contribution_id,
    ]);

    // check if this is a SEPA transaction (doesn't concern us)
    if (self::isSDD($recurring_contribution['payment_instrument_id'])) {
      return;
    }

    // see if this recurring contribution is from Twingle
    if (!self::isTwingleRecurringContribution($recurring_contribution_id, $recurring_contribution)) {
      return;
    }

    // check if it's really a termination (i.e. current status is 2 or 5)
    if (!in_array($recurring_contribution['contribution_status_id'], [2, 5])) {
      return;
    }

    // this _IS_ on of the cases where we should step in:
    CRM_Twingle_Tools::processRecurringContributionTermination(
      $recurring_contribution_id,
      $recurring_contribution
    );
  }

  /**
   * @param $recurring_contribution_id    int recurring contribution ID to check
   * @param $recurring_contribution       array recurring contribution data, optional
   * @return bool|null  true, false or null if can't be determined
   * @throws \CRM_Core_Exception
   */
  public static function isTwingleRecurringContribution($recurring_contribution_id, $recurring_contribution = NULL) {
    // this currently only works with prefixes
    $prefix = Civi::settings()->get('twingle_prefix');
    if (empty($prefix)) {
      return NULL;
    }

    // load recurring contribution if necessary
    if (empty($recurring_contribution['trxn_id'])) {
      $recurring_contribution = civicrm_api3('ContributionRecur', 'getsingle', ['id' => $recurring_contribution_id]);
    }

    // check if it's a Twingle contribution by checking the prefix
    // fixme: better ways (e.g. tags) should be used to mark twingle contributions
    return (substr($recurring_contribution['trxn_id'], 0, strlen($prefix)) == $prefix);
  }

  /**
   * Execute the recurring contribution protection
   *
   * @param int $recurring_contribution_id
   *   Recurring contribution ID.
   * @param array<mixed> $recurring_contribution
   *   Recurring contribution fields.
   * @throws Exception could be one of the measures
   */
  public static function processRecurringContributionTermination(
    int $recurring_contribution_id,
    array $recurring_contribution
  ) {
    // check if we're suspended
    if (self::$protection_suspended) {
      return;
    }

    $protection_mode = Civi::settings()->get('twingle_protect_recurring');
    switch ($protection_mode) {
      case CRM_Twingle_Config::RCUR_PROTECTION_OFF:
        // do nothing
        break;

      case CRM_Twingle_Config::RCUR_PROTECTION_EXCEPTION:
        // phpcs:disable Generic.Files.LineLength.TooLong
        throw new BaseException(E::ts(
          'This is a Twingle recurring contribution. It should be terminated through the Twingle interface, otherwise it will still be collected.'
        ));

        // phpcs:enable

      case CRM_Twingle_Config::RCUR_PROTECTION_ACTIVITY:
        // create contact source activity
        // first: get the contact ID
        if (!empty($recurring_contribution['contact_id'])) {
          $target_id = (int) $recurring_contribution['contact_id'];
        }
        else {
          $target_id = (int) civicrm_api3('ContributionRecur', 'getvalue', [
            'id'     => $recurring_contribution_id,
            'return' => 'contact_id',
          ]);
        }
        if (!empty($recurring_contribution['trxn_id'])) {
          $trxn_id = $recurring_contribution['trxn_id'];
        }
        else {
          $trxn_id = civicrm_api3('ContributionRecur', 'getvalue', [
            'id'     => $recurring_contribution_id,
            'return' => 'trxn_id',
          ]);
        }

        try {
          civicrm_api3('Activity', 'create', [
            'activity_type_id'   => Civi::settings()->get('twingle_protect_recurring_activity_type'),
            'subject'            => Civi::settings()->get('twingle_protect_recurring_activity_subject'),
            'activity_date_time' => date('YmdHis'),
            'target_id'          => $target_id,
            'assignee_id'        => Civi::settings()->get('twingle_protect_recurring_activity_assignee'),
            'status_id'          => Civi::settings()->get('twingle_protect_recurring_activity_status'),
            // phpcs:disable Generic.Files.LineLength.TooLong
            'details'            => E::ts(
              "Recurring contribution [%1] (Transaction ID '%2') was terminated by a user. You need to end the corresponding record in Twingle as well, or it will still be collected.",
              [1 => $recurring_contribution_id, 2 => $trxn_id]
            ),
            // phpcs:enable
            'source_contact_id'  => CRM_Core_Session::getLoggedInContactID(),
          ]);
        }
        catch (Exception $ex) {
          Civi::log()->warning("TwingleAPI: Couldn't create recurring protection activity: " . $ex->getMessage());
        }
        break;

      default:
        Civi::log()->warning("TwingleAPI: Unknown recurring contribution protection mode: '{$protection_mode}'");
        break;
    }
  }

  /**
   * Check if the given payment instrument is SEPA
   *
   * @param string $payment_instrument_id
   * @return boolean
   */
  public static function isSDD(string $payment_instrument_id) {
    static $sepa_payment_instruments = NULL;
    if ($sepa_payment_instruments === NULL) {
      // init with instrument names
      $sepa_payment_instruments = ['FRST', 'RCUR', 'OOFF'];

      // lookup and add instrument IDs
      $lookup = civicrm_api3('OptionValue', 'get', [
        'option_group_id' => 'payment_instrument',
        'name'            => ['IN' => $sepa_payment_instruments],
        'return'          => 'value',
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
   * @param int $contribution_id contribution ID *or* recurring contribution ID
   * @return array<string, mixed>|null mandate or null
   */
  public static function getMandateFor(int $contribution_id): ?array {
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
      }
      catch (Exception $ex) {
        Civi::log()->warning("CRM_Twingle_Tools::getMandate failed for [{$contribution_id}]: " . $ex->getMessage());
      }
    }
    return NULL;
  }

}
