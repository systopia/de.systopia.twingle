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
 * TwingleDonation.Cancel API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $params description of fields supported by this API call
 *
 * @return void
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_twingle_donation_Cancel_spec(&$params) {
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
  $params['cancelled_at'] = array(
    'name'         => 'cancelled_at',
    'title'        => 'Cancelled at',
    'type'         => CRM_Utils_Type::T_INT,
    'api.required' => 1,
    'description'  => 'The date when the donation was cancelled, format: YYYYMMDD.',
  );
  $params['cancel_reason'] = array(
    'name'         => 'cancel_reason',
    'title'        => 'Cancel reason',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'description'  => 'The reason for the donation being cancelled.',
  );
}

/**
 * TwingleDonation.Cancel API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_twingle_donation_Cancel($params) {
  try {
    // Validate date for parameter "cancelled_at".
    if (!DateTime::createFromFormat('Ymd', $params['cancelled_at'])) {
      throw new CiviCRM_API3_Exception(
        E::ts('Invalid date for parameter "cancelled_at".'),
        'invalid_format'
      );
    }

    $contribution = civicrm_api3('Contribution', 'getsingle', array(
      'trxn_id' => $params['trx_id'],
    ));
    // TODO: Can recurring contributions be cancelled? End SEPA mandates?
    $contribution = civicrm_api3('Contribution', 'create', array(
      'id' => $contribution['id'],
      'cancel_date' => $params['cancelled_at'],
      'contribution_status_id' => 'Cancelled',
      'cancel_reason' => $params['cancel_reason'],
    ));

    $result = civicrm_api3_create_success($contribution);
  }
  catch (CiviCRM_API3_Exception $exception) {
    $result = civicrm_api3_create_error($exception->getMessage());
  }

  return $result;
}
