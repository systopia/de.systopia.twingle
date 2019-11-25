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

use CRM_Twingle_ExtensionUtil as E;

/**
 * TwingleDonation.Endrecurring API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $params description of fields supported by this API call
 *
 * @return void
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_twingle_donation_endrecurring_spec(&$params) {
  $params['project_id'] = array(
    'name' => 'project_id',
    'title' => E::ts('Project ID'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'description' => E::ts('The Twingle project ID.'),
  );
  $params['trx_id'] = array(
    'name' => 'trx_id',
    'title' => E::ts('Transaction ID'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'description' => E::ts('The unique transaction ID of the donation'),
  );
  $params['ended_at'] = array(
    'name'         => 'ended_at',
    'title'        => E::ts('Ended at'),
    'type'         => CRM_Utils_Type::T_INT,
    'api.required' => 1,
    'description'  => E::ts('The date when the recurring donation was ended, format: YmdHis.'),
  );
}

/**
 * TwingleDonation.Endrecurring API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 */
function civicrm_api3_twingle_donation_endrecurring($params) {
  // Log call if debugging is enabled within civicrm.settings.php.
  if (defined('TWINGLE_API_LOGGING') && TWINGLE_API_LOGGING) {
    CRM_Core_Error::debug_log_message('TwingleDonation.Endrecurring: ' . json_encode($params, JSON_PRETTY_PRINT));
  }

  try {
    // Validate date for parameter "ended_at".
    if (!DateTime::createFromFormat('YmdHis', $params['ended_at'])) {
      throw new CiviCRM_API3_Exception(
        E::ts('Invalid date for parameter "ended_at".'),
        'invalid_format'
      );
    }

    $default_profile = CRM_Twingle_Profile::getProfile('default');
    $contribution = civicrm_api3('ContributionRecur', 'getsingle', array(
      'trxn_id' => $default_profile->getTransactionID($params['trx_id']),
    ));

    // End SEPA mandate (which ends the associated recurring contribution) or
    // recurring contributions.
    if (
      CRM_Twingle_Submission::civiSepaEnabled()
        && CRM_Twingle_Tools::isSDD($contribution['payment_instrument_id'])
    ) {
      // END SEPA MANDATE
      $mandate = CRM_Twingle_Tools::getMandateFor($contribution['id']);
      if (!$mandate) {
        throw new CiviCRM_API3_Exception(
            E::ts("SEPA Mandate for recurring contribution [%1 not found.", [1 => $contribution['id']]),
            'api_error'
        );
      }

      $mandate_id = $mandate['id'];
      $end_date = date_create_from_format('YmdHis', $params['ended_at']);
      if ($end_date) {
        // Mandates can not be terminated in the past:
        $end_date = date('Ymd', max(
            time(),
            $end_date->getTimestamp()));
      } else {
        // end date couldn't be parsed, use 'now'
        $end_date = date('Ymd');
      }

      // verify that the mandate has not been terminated in the past
      if ($mandate['status'] != 'FRST' && $mandate['status'] != 'RCUR') {
        throw new CiviCRM_API3_Exception(
            E::ts("SEPA Mandate [%1] already terminated.", [1 => $mandate_id]),
            'api_error'
        );
      }

      if (!CRM_Sepa_BAO_SEPAMandate::terminateMandate(
        $mandate_id,
        $end_date,
        E::ts('Mandate closed by TwingleDonation.Endrecurring API call')
      )) {
        throw new CiviCRM_API3_Exception(
          E::ts('Could not terminate SEPA mandate'),
          'api_error'
        );
      }
      $contribution = civicrm_api3('ContributionRecur', 'getsingle', array(
        'id' => $contribution['id'],
      ));
    }
    else {
      // END RECURRING CONTRIBUTION
      CRM_Twingle_Tools::$protection_suspended = TRUE;
      $contribution = civicrm_api3('ContributionRecur', 'create', array(
          'id'                     => $contribution['id'],
          'end_date'               => $params['ended_at'],
          'contribution_status_id' => CRM_Twingle_Submission::CONTRIBUTION_STATUS_COMPLETED,
      ));
      CRM_Twingle_Tools::$protection_suspended = FALSE;
    }

    $result = civicrm_api3_create_success($contribution);
  }
  catch (CiviCRM_API3_Exception $exception) {
    $result = civicrm_api3_create_error($exception->getMessage());
  }

  return $result;
}
