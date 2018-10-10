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
 * TwingleDonation.EndRecurring API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $params description of fields supported by this API call
 *
 * @return void
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_twingle_donation_EndRecurring_spec(&$params) {
  $params['project_id'] = array(
    'name' => 'project_id',
    'title' => 'Project ID',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'description' => 'The Twingle project ID.',
  );
  $params['trx_id'] = array(
    'name' => 'trx_id',
    'title' => 'Transaction ID',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'description' => 'The unique transaction ID of the donation',
  );
  $params['ended_at'] = array(
    'name'         => 'ended_at',
    'title'        => 'Ended at',
    'type'         => CRM_Utils_Type::T_INT,
    'api.required' => 1,
    'description'  => 'The date when the recurring donation was ended, format: YYYYMMDD.',
  );
}

/**
 * TwingleDonation.EndRecurring API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 */
function civicrm_api3_twingle_donation_EndRecurring($params) {
  try {
    // Validate date for parameter "ended_at".
    if (!DateTime::createFromFormat('Ymd', $params['ended_at'])) {
      throw new CiviCRM_API3_Exception(
        E::ts('Invalid date for parameter "ended_at".'),
        'invalid_format'
      );
    }

    $contribution = civicrm_api3('ContributionRecur', 'getsingle', array(
      'trxn_id' => $params['trx_id'],
    ));
    // End SEPA mandate (which ends the associated recurring contribution) or
    // recurring contributions.
    if (
      CRM_Twingle_Submission::civiSepaEnabled()
      && CRM_Sepa_Logic_Settings::isSDD($contribution)
    ) {
      $mandate_id = CRM_Sepa_Logic_Settings::getMandateFor($contribution['id']);
      if (!CRM_Sepa_BAO_SEPAMandate::terminateMandate($mandate_id, $params['ended_at'])) {
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
      $contribution = civicrm_api3('ContributionRecur', 'create', array(
        'id' => $contribution['id'],
        'end_date' => $params['ended_at'],
        'contribution_status_id' => 'Completed',
      ));
    }

    $result = civicrm_api3_create_success($contribution);
  }
  catch (CiviCRM_API3_Exception $exception) {
    $result = civicrm_api3_create_error($exception->getMessage());
  }

  return $result;
}
