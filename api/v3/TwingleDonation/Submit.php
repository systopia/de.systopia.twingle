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
use Civi\Twingle\Exceptions\BaseException;
use Civi\Api4\Note;

/**
 * TwingleDonation.Submit API specification
 * This is used for documentation and validation.
 *
 * @param array<string,array<string,mixed>> $params
 *   Description of fields supported by this API call.
 *
 * @return void
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_twingle_donation_Submit_spec(&$params) {
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
  $params['confirmed_at'] = [
    'name'         => 'confirmed_at',
    'title'        => E::ts('Confirmed at'),
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'description'  => E::ts('The date when the donation was issued, format: YmdHis.'),
  ];
  $params['booked_at'] = [
    'name'         => 'booked_at',
    'title'        => E::ts('Booked at'),
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description'  => E::ts('The date when the donation was booked, format: YmdHis.'),
  ];
  $params['purpose'] = [
    'name'         => 'purpose',
    'title'        => E::ts('Purpose'),
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description'  => E::ts('The purpose of the donation.'),
  ];
  $params['amount'] = [
    'name'         => 'amount',
    'title'        => E::ts('Amount'),
    'type'         => CRM_Utils_Type::T_INT,
    'api.required' => 1,
    'description'  => E::ts('The donation amount in minor currency unit.'),
  ];
  $params['currency'] = [
    'name' => 'currency',
    'title' => E::ts('Currency'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'description' => E::ts('The ISO-4217 currency code of the donation.'),
  ];
  $params['newsletter'] = [
    'name' => 'newsletter',
    'title' => E::ts('Newsletter'),
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.required' => 0,
    'description' => E::ts('Whether to subscribe the contact to the newsletter group defined in the profile.'),
  ];
  $params['postinfo'] = [
    'name' => 'postinfo',
    'title' => E::ts('Postal mailing'),
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.required' => 0,
    'description' => E::ts('Whether to subscribe the contact to the postal mailing group defined in the profile.'),
  ];
  $params['donation_receipt'] = [
    'name' => 'donation_receipt',
    'title' => E::ts('Donation receipt'),
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.required' => 0,
    'description' => E::ts('Whether the contact requested a donation receipt.'),
  ];
  $params['payment_method'] = [
    'name' => 'payment_method',
    'title' => E::ts('Payment method'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'description' => E::ts('The Twingle payment method used for the donation.'),
  ];
  $params['donation_rhythm'] = [
    'name' => 'donation_rhythm',
    'title' => E::ts('Donation rhythm'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'description' => E::ts('The interval which the donation is recurring in.'),
  ];
  $params['debit_iban'] = [
    'name' => 'debit_iban',
    'title' => E::ts('SEPA IBAN'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => E::ts('The IBAN for SEPA Direct Debit payments, conforming with ISO 13616-1:2007.'),
  ];
  $params['debit_bic'] = [
    'name' => 'debit_bic',
    'title' => E::ts('SEPA BIC'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => E::ts('The BIC for SEPA Direct Debit payments, conforming with ISO 9362.'),
  ];
  $params['debit_mandate_reference'] = [
    'name' => 'debit_mandate_reference',
    'title' => E::ts('SEPA Direct Debit Mandate reference'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => E::ts('The mandate reference for SEPA Direct Debit payments.'),
  ];
  $params['debit_account_holder'] = [
    'name' => 'debit_account_holder',
    'title' => E::ts('SEPA Direct Debit Account holder'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => E::ts('The account holder for SEPA Direct Debit payments.'),
  ];
  $params['is_anonymous'] = [
    'name' => 'is_anonymous',
    'title' => E::ts('Anonymous donation'),
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.required' => 0,
    'api.default' => 0,
    'description' => E::ts('Whether the donation is submitted anonymously.'),
  ];
  $params['user_gender'] = [
    'name' => 'user_gender',
    'title' => E::ts('Gender'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => E::ts('The gender of the contact.'),
  ];
  $params['user_birthdate'] = [
    'name' => 'user_birthdate',
    'title' => E::ts('Date of birth'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => E::ts('The date of birth of the contact, format: Ymd.'),
  ];
  $params['user_title'] = [
    'name' => 'user_title',
    'title' => E::ts('Formal title'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => E::ts('The formal title of the contact.'),
  ];
  $params['user_email'] = [
    'name' => 'user_email',
    'title' => E::ts('Email address'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => E::ts('The e-mail address of the contact.'),
  ];
  $params['user_firstname'] = [
    'name' => 'user_firstname',
    'title' => E::ts('First name'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => E::ts('The first name of the contact.'),
  ];
  $params['user_lastname'] = [
    'name' => 'user_lastname',
    'title' => E::ts('Last name'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => E::ts('The last name of the contact.'),
  ];
  $params['user_street'] = [
    'name' => 'user_street',
    'title' => E::ts('Street address'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => E::ts('The street address of the contact.'),
  ];
  $params['user_postal_code'] = [
    'name' => 'user_postal_code',
    'title' => E::ts('Postal code'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => E::ts('The postal code of the contact.'),
  ];
  $params['user_city'] = [
    'name' => 'user_city',
    'title' => E::ts('City'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => E::ts('The city of the contact.'),
  ];
  $params['user_country'] = [
    'name' => 'user_country',
    'title' => E::ts('Country'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => E::ts('The country of the contact.'),
  ];
  $params['user_telephone'] = [
    'name' => 'user_telephone',
    'title' => E::ts('Telephone'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => E::ts('The telephone number of the contact.'),
  ];
  $params['user_company'] = [
    'name' => 'user_company',
    'title' => E::ts('Company'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => E::ts('The company of the contact.'),
  ];
  $params['user_language'] = [
    'name' => 'user_language',
    'title' => E::ts('Language'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => E::ts('The preferred language of the contact. A 2-digit ISO-639-1 language code.'),
  ];
  $params['user_extrafield'] = [
    'name' => 'user_extrafield',
    'title' => E::ts('User extra field'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => E::ts('Additional information of the contact.'),
  ];
  $params['campaign_id'] = [
    'name' => 'campaign_id',
    'title' => E::ts('Campaign ID'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => E::ts('The CiviCRM ID of a campaign to assign the contribution.'),
  ];
  $params['custom_fields'] = [
    'name' => 'custom_fields',
    'title' => E::ts('Custom fields'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description'  => E::ts('Additional information for either the contact or the (recurring) contribution.'),
  ];
  $params['products'] = [
    'name' => 'products',
    'title' => E::ts('Products'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => E::ts('Products ordered via TwingleShop'),
  ];
  $params['remarks'] = [
    'name' => 'remarks',
    'title' => E::ts('Remarks'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => E::ts('Additional remarks for the donation.'),
  ];
}

/**
 * TwingleDonation.Submit API
 *
 * @phpstan-param array{
 *   project_id: string,
 * } $params
 * @phpstan-return array<string, mixed>
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 */
function civicrm_api3_twingle_donation_Submit($params) {
  // Log call if debugging is enabled within civicrm.settings.php.
  if (defined('TWINGLE_API_LOGGING') && TWINGLE_API_LOGGING) {
    Civi::log()->debug('TwingleDonation.Submit: ' . json_encode($params, JSON_PRETTY_PRINT));
  }

  try {
    // Prepare results array.
    $resultValues = [];

    // Get the profile defined for the given form ID, or the default profile
    // if none matches.
    $profile = CRM_Twingle_Profile::getProfileForProject($params['project_id']);
    $profile->logAccess();

    // Validate submitted parameters
    CRM_Twingle_Submission::validateSubmission($params, $profile);

    $customFieldValues = CRM_Twingle_Submission::getCustomFieldValues($params, $profile);

    // Create contact(s).
    CRM_Twingle_Submission::handleContacts(
      $params,
      $profile,
      $customFieldValues,
      $resultValues
    );
    $contactId = $resultValues['contact'];
    $organizationId = $resultValues['organization'] ?? NULL;

    CRM_Twingle_Submission::handleGroups($params, $profile, $resultValues);

    CRM_Twingle_Submission::handleTransaction(
      isset($organizationId) ? $organizationId : $contactId,
      $profile,
      $params,
      $customFieldValues,
      $resultValues
    );

    CRM_Twingle_Submission::handleMembership($params, $profile, $contactId, $resultValues);

    $result = civicrm_api3_create_success($resultValues);
  }
  catch (Exception $exception) {
    $result = civicrm_api3_create_error($exception->getMessage());
  }

  return $result;
}
