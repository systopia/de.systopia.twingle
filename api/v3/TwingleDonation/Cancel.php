<?php
/*------------------------------------------------------------+
| SYSTOPIA Twingle Integration                                |
| Copyright (C) 2018 SYSTOPIA                                 |
| Author: J. Schuppe (schuppe@systopia.de)                    |
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

/**
 * TwingleDonation.Cancel API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array<string, array<string, mixed>> $params description of fields supported by this API call
 *
 * @return void
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_twingle_donation_Cancel_spec(&$params) {
  $params['project_id'] = [
    'name' => 'project_id',
    'title' => E::ts('Project ID'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'description' => E::ts('The Twingle project ID.'),
  ];
  $params['trx_id'] = [
    'name' => 'trx_id',
    'title' => E::ts('Transaction ID'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'description' => E::ts('The unique transaction ID of the donation'),
  ];
  $params['cancelled_at'] = [
    'name'         => 'cancelled_at',
    'title'        => E::ts('Cancelled at'),
    'type'         => CRM_Utils_Type::T_INT,
    'api.required' => 1,
    'description'  => E::ts('The date when the donation was cancelled, format: YmdHis.'),
  ];
  $params['cancel_reason'] = [
    'name'         => 'cancel_reason',
    'title'        => E::ts('Cancel reason'),
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'description'  => E::ts('The reason for the donation being cancelled.'),
  ];
}

/**
 * TwingleDonation.Cancel API
 *
 * @param array<string, mixed> $params
 * @return array<string, mixed> API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 */
function civicrm_api3_twingle_donation_Cancel($params) {
  // Log call if debugging is enabled within civicrm.settings.php.
  if (defined('TWINGLE_API_LOGGING') && TWINGLE_API_LOGGING) {
    Civi::log()->debug('TwingleDonation.Cancel: ' . json_encode($params, JSON_PRETTY_PRINT));
  }

  try {
    // Validate date for parameter "cancelled_at".
    if (!DateTime::createFromFormat('YmdHis', $params['cancelled_at'])) {
      throw new CRM_Core_Exception(
        E::ts('Invalid date for parameter "cancelled_at".'),
        'invalid_format'
      );
    }

    // Retrieve (recurring) contribution.
    $default_profile = CRM_Twingle_Profile::getProfile('default');
    try {
      $contribution = civicrm_api3('Contribution', 'getsingle', [
        'trxn_id' => $default_profile->getTransactionID($params['trx_id']),
      ]);
      $contribution_type = 'Contribution';
    }
    catch (CRM_Core_Exception $exception) {
      $contribution = civicrm_api3('ContributionRecur', 'getsingle', [
        'trxn_id' => $default_profile->getTransactionID($params['trx_id']),
      ]);
      $contribution_type = 'ContributionRecur';
    }

    if (
      CRM_Twingle_Submission::civiSepaEnabled()
        && CRM_Twingle_Tools::isSDD($contribution['payment_instrument_id'])
    ) {
      // End SEPA mandate if applicable.
      $mandate = CRM_Twingle_Tools::getMandateFor((int) $contribution['id']);
      if (!$mandate) {
        throw new CRM_Core_Exception(
            E::ts('SEPA Mandate for contribution [%1 not found.', [1 => $contribution['id']]),
            'api_error'
        );
      }
      $mandate_id = (int) $mandate['id'];

      // Mandates can not be terminated in the past.
      $end_date = date_create_from_format('YmdHis', $params['cancelled_at']);
      if (FALSE !== $end_date) {
        // Mandates can not be terminated in the past:
        $end_date = date('Ymd', max(
            time(),
            $end_date->getTimestamp()));
      }
      else {
        // end date couldn't be parsed, use 'now'
        $end_date = date('Ymd');
      }

      if (!CRM_Sepa_BAO_SEPAMandate::terminateMandate(
        $mandate_id,
        $end_date,
        $params['cancel_reason']
      )) {
        throw new CRM_Core_Exception(
          E::ts('Could not terminate SEPA mandate'),
          'api_error'
        );
      }

      // Retrieve updated contribution for return value.
      $contribution = civicrm_api3($contribution_type, 'getsingle', [
        'id' => $contribution['id'],
      ]);
    }
    else {
      // regular contribution
      CRM_Twingle_Tools::$protection_suspended = TRUE;
      $contribution = civicrm_api3($contribution_type, 'create', [
        'id' => $contribution['id'],
        'cancel_date' => $params['cancelled_at'],
        'contribution_status_id' => 'Cancelled',
        'cancel_reason' => $params['cancel_reason'],
      ]);
      CRM_Twingle_Tools::$protection_suspended = FALSE;
    }

    $result = civicrm_api3_create_success($contribution);
  }
  catch (Exception $exception) {
    $result = civicrm_api3_create_error($exception->getMessage());
  }

  return $result;
}
