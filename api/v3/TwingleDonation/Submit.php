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
 * @param array<string, mixed> $params
 * @return array<string, mixed> API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 */
function civicrm_api3_twingle_donation_Submit($params) {
  // Log call if debugging is enabled within civicrm.settings.php.
  if (defined('TWINGLE_API_LOGGING') && TWINGLE_API_LOGGING) {
    Civi::log()->debug('TwingleDonation.Submit: ' . json_encode($params, JSON_PRETTY_PRINT));
  }

  try {
    // Copy submitted parameters.
    $original_params = $params;

    // Prepare results array.
    $result_values = [];

    // Get the profile defined for the given form ID, or the default profile
    // if none matches.
    $profile = CRM_Twingle_Profile::getProfileForProject($params['project_id']);
    $profile->logAccess();

    // Validate submitted parameters
    CRM_Twingle_Submission::validateSubmission($params, $profile);

    // Do not process an already existing contribution with the given
    // transaction ID.
    $existing_contribution = civicrm_api3('Contribution', 'get', [
      'trxn_id' => $profile->getTransactionID($params['trx_id']),
    ]);
    $existing_contribution_recur = civicrm_api3('ContributionRecur', 'get', [
      'trxn_id' => $profile->getTransactionID($params['trx_id']),
    ]);
    if ($existing_contribution['count'] > 0 || $existing_contribution_recur['count'] > 0) {
      throw new CRM_Core_Exception(
        E::ts('Contribution with the given transaction ID already exists.'),
        'api_error'
      );
    }

    // Extract custom field values using the profile's mapping of Twingle fields
    // to CiviCRM custom fields.
    $custom_fields = [];
    if (is_array($params['custom_fields'])) {
      $custom_field_mapping = $profile->getCustomFieldMapping();

      // Make all params available for custom field mapping
      $allowed_params = [];
      _civicrm_api3_twingle_donation_Submit_spec($allowed_params);
      $params['custom_fields'] += array_intersect_key($params, $custom_field_mapping, $allowed_params);

      foreach ($params['custom_fields'] as $twingle_field => $value) {
        if (isset($custom_field_mapping[$twingle_field])) {
          // Get custom field definition to store values by entity the field
          // extends.
          $custom_field_id = substr($custom_field_mapping[$twingle_field], strlen('custom_'));
          $custom_field = civicrm_api3('CustomField', 'getsingle', [
            'id' => $custom_field_id,
            // Chain a CustomGroup.getsingle API call.
            'api.CustomGroup.getsingle' => [],
          ]);
          $entity = $custom_field['api.CustomGroup.getsingle']['extends'];
          $custom_fields[$entity][$custom_field_mapping[$twingle_field]] = $value;
        }
      }
    }

    // Create contact(s).
    if ($params['is_anonymous']) {
      // Retrieve the ID of the contact to use for anonymous donations defined
      // within the profile
      $contact_id = civicrm_api3('Contact', 'getsingle', [
        'id' => $profile->getAttribute('anonymous_contact_id'),
      ])['id'];
    }
    else {
      // Prepare parameter mapping for address.
      foreach ([
        'user_street' => 'street_address',
        'user_postal_code' => 'postal_code',
        'user_city' => 'city',
        'user_country' => 'country',
      ] as $address_param => $address_component) {
        if (isset($params[$address_param]) && '' !== $params[$address_param]) {
          $params[$address_component] = $params[$address_param];
          unset($params[$address_param]);
        }
      }

      // Remove address data when any address component that is configured as
      // required is missing.
      // See https://github.com/systopia/de.systopia.twingle/issues/47
      foreach ($profile->getAttribute('required_address_components', []) as $required_address_component) {
        if (empty($params[$required_address_component])) {
          foreach ([
            'street_address',
            'postal_code',
            'city',
            'country',
          ] as $address_param) {
            unset($params[$address_param]);
          }
          break;
        }
      }

      // Prepare parameter mapping for organisation.
      if (is_string($params['user_company']) && '' !== $params['user_company']) {
        $params['organization_name'] = $params['user_company'];
        unset($params['user_company']);
      }

      // Remove parameter "id".
      if (isset($params['id'])) {
        unset($params['id']);
      }

      // Add configured location type to parameters.
      $params['location_type_id'] = (int) $profile->getAttribute('location_type_id');

      // Exclude address for now when retrieving/creating the individual contact
      // as we are checking organisation address first and share it with the
      // individual.
      $submitted_address = [];
      foreach ([
        'street_address',
        'postal_code',
        'city',
        'country',
        'location_type_id',
      ] as $address_component) {
        if (!empty($params[$address_component])) {
          $submitted_address[$address_component] = $params[$address_component];
          unset($params[$address_component]);
        }
      }

      // Get the ID of the contact matching the given contact data, or create a
      // new contact if none exists for the given contact data.
      $contact_data = [];
      foreach ([
        'user_firstname' => 'first_name',
        'user_lastname' => 'last_name',
        'gender_id' => 'gender_id',
        'user_birthdate' => 'birth_date',
        'user_email' => 'email',
        'user_telephone' => 'phone',
        'user_language' => 'preferred_language',
        'user_title' => 'formal_title',
        'debit_iban' => 'iban',
      ] as $contact_param => $contact_component) {
        if (!empty($params[$contact_param])) {
          $contact_data[$contact_component] = $params[$contact_param];
        }
      }

      // Get the prefix ID defined within the profile
      if (
        isset($params['user_gender'])
        && is_numeric($prefix_id = $profile->getAttribute('prefix_' . $params['user_gender']))
      ) {
        $contact_data['prefix_id'] = $prefix_id;
      }

      // Add custom field values.
      if (isset($custom_fields['Contact'])) {
        $contact_data += $custom_fields['Contact'];
      }
      if (isset($custom_fields['Individual'])) {
        $contact_data += $custom_fields['Individual'];
      }

      // Organisation lookup.
      if (is_string($params['organization_name']) && '' !== $params['organization_name']) {
        $organisation_data = [
          'organization_name' => $params['organization_name'],
        ];

        // Add custom field values.
        if (isset($custom_fields['Organization'])) {
          $organisation_data += $custom_fields['Organization'];
        }

        if ([] !== $submitted_address) {
          $organisation_data += $submitted_address;
          // Use configured location type for organisation address.
          $organisation_data['location_type_id'] = (int) $profile->getAttribute('location_type_id_organisation');
        }
        if (!is_int($organisation_id = CRM_Twingle_Submission::getContact(
          'Organization',
          $organisation_data,
          $profile,
          $params
        ))) {
          throw new CRM_Core_Exception(
            E::ts('Organisation contact could not be found or created.'),
            'api_error'
          );
        }
      }
      elseif ([] !== $submitted_address) {
        $contact_data += $submitted_address;
      }

      if (!is_int($contact_id = CRM_Twingle_Submission::getContact(
        'Individual',
        $contact_data,
        $profile,
        $params
      ))) {
        throw new CRM_Core_Exception(
          E::ts('Individual contact could not be found or created.'),
          'api_error'
        );
      }

      // Create contact notes.
      /** @phpstan-var array<string> $contact_note_mappings */
      $contact_note_mappings = $profile->getAttribute('map_as_contact_notes', []);
      foreach (['user_extrafield'] as $target) {
        if (
          isset($params[$target])
          && '' !== $params[$target]
          && in_array($target, $contact_note_mappings, TRUE)
        ) {
          Note::create(FALSE)
            ->addValue('entity_table', 'civicrm_contact')
            ->addValue('entity_id', $contact_id)
            ->addValue('note', $params[$target])
            ->execute();
        }
      }

      // Share organisation address with individual contact, using configured
      // location type for organisation address.
      $address_shared = (
        isset($organisation_id)
        && CRM_Twingle_Submission::shareWorkAddress(
          $contact_id,
          $organisation_id,
          (int) $profile->getAttribute('location_type_id_organisation')
        )
      );

      // Create employer relationship between organization and individual.
      if (isset($organisation_id)) {
        CRM_Twingle_Submission::updateEmployerRelation($contact_id, $organisation_id);
      }
    }

    $result_values['contact'] = $contact_id;
    if (isset($organisation_id)) {
      $result_values['organization'] = $organisation_id;
    }

    // If usage of double opt-in is selected, use MailingEventSubscribe.create
    // to add contact to newsletter groups defined in the profile
    $result_values['newsletter']['newsletter_double_opt_in']
      = (bool) $profile->getAttribute('newsletter_double_opt_in')
      ? 'true'
      : 'false';
    if (
      (bool) $profile->getAttribute('newsletter_double_opt_in')
      && (bool) ($params['newsletter'] ?? FALSE)
      && is_array($groups = $profile->getAttribute('newsletter_groups'))
    ) {
      $group_memberships = array_column(
        civicrm_api3(
          'GroupContact',
          'get',
          [
            'sequential' => 1,
            'contact_id' => $contact_id,
          ]
        )['values'],
        'group_id'
      );
      foreach ($groups as $group_id) {
        $is_public_group = civicrm_api3(
          'Group',
            'getsingle',
            [
              'id' => (int) $group_id,
            ]
          )['visibility'] == 'Public Pages';
        if (!in_array($group_id, $group_memberships, FALSE) && $is_public_group) {
          $result_values['newsletter'][][$group_id] = civicrm_api3(
            'MailingEventSubscribe',
            'create',
            [
              'email' => $params['user_email'],
              'group_id' => (int) $group_id,
              'contact_id' => $contact_id,
            ]
          );
        }
        elseif ($is_public_group) {
          $result_values['newsletter'][] = $group_id;
        }
      }
      // If requested, add contact to newsletter groups defined in the profile.
    }
    elseif (
      (bool) ($params['newsletter'] ?? FALSE)
      && is_array($groups = $profile->getAttribute('newsletter_groups'))
    ) {
      foreach ($groups as $group_id) {
        civicrm_api3(
          'GroupContact',
          'create',
          [
            'group_id' => $group_id,
            'contact_id' => $contact_id,
          ]
        );

        $result_values['newsletter'][] = $group_id;
      }
    }

    // If requested, add contact to postinfo groups defined in the profile.
    if (
      (bool) ($params['postinfo'] ?? FALSE)
      && is_array($groups = $profile->getAttribute('postinfo_groups'))
    ) {
      foreach ($groups as $group_id) {
        civicrm_api3('GroupContact', 'create', [
          'group_id' => $group_id,
          'contact_id' => $contact_id,
        ]);

        $result_values['postinfo'][] = $group_id;
      }
    }

    // If requested, add contact to donation_receipt groups defined in the
    // profile. If an organisation is provided, add it to the groups instead.
    // (see issue #83)
    if (
      (bool) ($params['donation_receipt'] ?? FALSE)
      && is_array($groups = $profile->getAttribute('donation_receipt_groups'))
    ) {
      foreach ($groups as $group_id) {
        civicrm_api3('GroupContact', 'create', [
          'group_id' => $group_id,
          'contact_id' => $organisation_id ?? $contact_id,
        ]);

        $result_values['donation_receipt'][] = $group_id;
      }
    }

    // Create contribution or SEPA mandate. Those attributes are valid for both,
    // single and recurring contributions.
    $contribution_data = [
      'contact_id' => (isset($organisation_id) ? $organisation_id : $contact_id),
      'currency' => $params['currency'],
      'trxn_id' => $profile->getTransactionID($params['trx_id']),
      'payment_instrument_id' => $params['payment_instrument_id'],
      'amount' => $params['amount'] / 100,
      'total_amount' => $params['amount'] / 100,
    ];

    // If the submission contains products, do not auto-create a line item
    if (!empty($params['products']) && $profile->isShopEnabled()) {
      $contribution_data['skipLineItem'] = 1;
    }

    // Add custom field values.
    if (isset($custom_fields['Contribution'])) {
      $contribution_data += $custom_fields['Contribution'];
    }

    // set campaign, subject to configuration
    CRM_Twingle_Submission::setCampaign($contribution_data, 'contribution', $params, $profile);

    if (NULL !== ($contribution_source = $profile->getAttribute('contribution_source'))) {
      $contribution_data['source'] = $contribution_source;
    }

    if (
      CRM_Twingle_Submission::civiSepaEnabled()
      && $contribution_data['payment_instrument_id'] == 'sepa'
    ) {
      // If CiviSEPA is installed and the financial type is a CiviSEPA-one,
      // create SEPA mandate (and recurring contribution, using "createfull" API
      // action).
      foreach ([
        'debit_iban',
        'debit_bic',
      ] as $sepa_attribute) {
        if (!isset($params[$sepa_attribute])) {
          throw new CRM_Core_Exception(
            E::ts('Missing attribute %1 for SEPA mandate', [
              1 => $sepa_attribute,
            ]),
            'invalid_format'
          );
        }
      }

      $creditor_id = $profile->getAttribute('sepa_creditor_id');
      if (!isset($creditor_id) || '' === $creditor_id) {
        throw new BaseException(
          E::ts('SEPA creditor is not configured for profile "%1".', [1 => $profile->getName()])
        );
      }

      // Compose mandate data from contribution data, ...
      $mandate_data =
        $contribution_data
        // ... CiviSEPA mandate attributes, ...
        // phpcs:ignore Drupal.Formatting.SpaceUnaryOperator.PlusMinus
        + [
          'type' => ($params['donation_rhythm'] == 'one_time' ? 'OOFF' : 'RCUR'),
          'iban' => $params['debit_iban'],
          'bic' => $params['debit_bic'],
          'reference' => $params['debit_mandate_reference'],
          // Signature date
          'date' => $params['confirmed_at'],
          // Earliest collection date.
          'start_date' => $params['confirmed_at'],
          'creditor_id' => $creditor_id,
        ]
        // ... and frequency unit and interval from a static mapping.
        // phpcs:ignore Drupal.Formatting.SpaceUnaryOperator.PlusMinus
        + CRM_Twingle_Submission::getFrequencyMapping($params['donation_rhythm']);
      // Add custom field values.
      if (isset($custom_fields['ContributionRecur'])) {
        $mandate_data += $custom_fields['ContributionRecur'];
      }
      if (NULL !== ($mandate_source = $profile->getAttribute('contribution_source'))) {
        $mandate_data['source'] = $mandate_source;
      }

      // Add cycle day for recurring contributions.
      if ($params['donation_rhythm'] != 'one_time') {
        $mandate_data['cycle_day'] = CRM_Twingle_Submission::getSEPACycleDay($params['confirmed_at'], $creditor_id);
        $mandate_data['financial_type_id'] = $profile->getAttribute('financial_type_id_recur');
      }
      else {
        $mandate_data['financial_type_id'] = $profile->getAttribute('financial_type_id');
      }

      // Let CiviSEPA set the correct payment instrument depending on the
      // mandate type.
      unset($mandate_data['payment_instrument_id']);

      // If requested, let CiviSEPA generate the mandate reference
      $use_own_mandate_reference = Civi::settings()->get('twingle_dont_use_reference');
      if ((bool) $use_own_mandate_reference) {
        unset($mandate_data['reference']);
      }

      // set campaign, subject to configuration
      CRM_Twingle_Submission::setCampaign($mandate_data, 'mandate', $params, $profile);

      // Create the mandate.
      $mandate = civicrm_api3('SepaMandate', 'createfull', $mandate_data);

      $result_values['sepa_mandate'] = CRM_Utils_Array::first($mandate['values']);

      // Add contribution data to result_values for later use
      $contribution_id = $result_values['sepa_mandate']['entity_id'];
      if ($contribution_id) {
        $contribution = civicrm_api3(
          'Contribution',
          'getsingle',
          ['id' => $contribution_id]
        );
        $result_values['contribution'] = $contribution;
      } else {
        $mandate_id = $result_values['sepa_mandate']['id'];
        $message = E::LONG_NAME . ": could not find contribution for sepa mandate $mandate_id";
        throw new CiviCRM_API3_Exception($message, 'api_error');
      }

      // Add products as line items to the contribution
      if (!empty($params['products']) && $profile->isShopEnabled()) {
        $line_items = CRM_Twingle_Submission::createLineItems($result_values, $params, $profile);
        $result_values['contribution']['line_items'] = $line_items;
      }
    }
    else {
      // Set financial type depending on donation rhythm. This applies for
      // initial recurring contributions and subsequent single contributions.
      if ($params['donation_rhythm'] != 'one_time') {
        $contribution_data['financial_type_id'] = $profile->getAttribute('financial_type_id_recur');
      }
      else {
        $contribution_data['financial_type_id'] = $profile->getAttribute('financial_type_id');
      }

      // Create (recurring) contribution.
      // Those will have a donation_rhythm different from "one_time" and no
      // parent_trx_id set.
      if (
        $params['donation_rhythm'] != 'one_time'
        && empty($params['parent_trx_id'])
      ) {
        // Create recurring contribution first.
        $contribution_recur_data =
          $contribution_data
          + [
            'contribution_status_id' => 'Pending',
            'start_date' => $params['confirmed_at'],
          ]
          + CRM_Twingle_Submission::getFrequencyMapping($params['donation_rhythm']);

        // Add custom field values.
        if (isset($custom_fields['ContributionRecur'])) {
          $contribution_recur_data += $custom_fields['ContributionRecur'];
          $contribution_data += $custom_fields['ContributionRecur'];
        }

        // set campaign, subject to configuration
        CRM_Twingle_Submission::setCampaign($contribution_data, 'recurring', $params, $profile);

        $contribution_recur = civicrm_api3('ContributionRecur', 'create', $contribution_recur_data);
        if ($contribution_recur['is_error']) {
          throw new CRM_Core_Exception(
            E::ts('Could not create recurring contribution.'),
            'api_error'
          );
        }
        $contribution_data['contribution_recur_id'] = $contribution_recur['id'];
        $contribution_data['financial_type_id'] = $contribution_recur_data['financial_type_id'];
      }

      // Create contribution.
      $contribution_data += [
        'contribution_status_id' => $profile->getAttribute(
          "pi_{$params['payment_method']}_status",
          CRM_Twingle_Submission::CONTRIBUTION_STATUS_COMPLETED
        ),
        'receive_date' => $params['confirmed_at'],
      ];

      // Assign to recurring contribution.
      if (!empty($params['parent_trx_id'])) {
        try {
          $parent_contribution = civicrm_api3('ContributionRecur', 'getsingle', [
            'trxn_id' => $profile->getTransactionID($params['parent_trx_id']),
          ]);
          $contribution_data['contribution_recur_id'] = $parent_contribution['id'];
        }
        catch (CRM_Core_Exception $exception) {
          $result_values['parent_contribution'] = E::ts(
            'Could not find recurring contribution with given parent transaction ID.'
          );
        }
      }

      $contribution = civicrm_api3('Contribution', 'create', $contribution_data);
      if ($contribution['is_error']) {
        throw new CRM_Core_Exception(
          E::ts('Could not create contribution'),
          'api_error'
        );
      }

      // Add notes to the contribution.
      /** @phpstan-var array<string> $contribution_note_mappings */
      $contribution_note_mappings = $profile->getAttribute('map_as_contribution_notes', []);
      foreach (['purpose', 'remarks'] as $target) {
        if (
          in_array($target, $contribution_note_mappings, TRUE)
          && isset($params[$target])
          && '' !== $params[$target]
        ) {
          Note::create(FALSE)
            ->addValue('entity_table', 'civicrm_contribution')
            ->addValue('entity_id', reset($contribution['values'])['id'])
            ->addValue('note', reset($params[$target]))
            ->execute();
        }
      }

      $result_values['contribution'] = $contribution['values'];

      // Add products as line items to the contribution
      if (!empty($params['products']) && $profile->isShopEnabled()) {
        $line_items = CRM_Twingle_Submission::createLineItems($result_values, $params, $profile);
        $result_values['contribution']['line_items'] = $line_items;
      }
    }

    // MEMBERSHIP CREATION

    // CHECK whether a membership should be created (based on profile settings and data provided)
    if ($params['donation_rhythm'] == 'one_time') {
      // membership creation based on one-off contributions
      $membership_type_id = $profile->getAttribute('membership_type_id');
    }
    else {
      // membership creation based on recurring contributions
      if (empty($params['parent_trx_id'])) {
        // this is the initial payment
        $membership_type_id = $profile->getAttribute('membership_type_id_recur');
      }
      else {
        // this is a follow-up recurring payment
        $membership_type_id = NULL;
      }
    }

    // CREATE the membership if required
    if (isset($membership_type_id)) {
      $membership_data = [
        'contact_id'         => $contact_id,
        'membership_type_id' => $membership_type_id,
      ];
      // set campaign, subject to configuration
      CRM_Twingle_Submission::setCampaign($membership_data, 'membership', $params, $profile);
      // set source
      if (!empty($membership_source = $profile->getAttribute('contribution_source'))) {
        $membership_data['source'] = $membership_source;
      }

      $membership = civicrm_api3('Membership', 'create', $membership_data);
      $result_values['membership'] = $membership;

      // call the postprocess API
      if ('' !== ($postprocess_call = $profile->getAttribute('membership_postprocess_call', ''))) {
        /** @var string $postprocess_call */
        [$pp_entity, $pp_action] = explode('.', $postprocess_call, 2);
        try {
          // gather the contribution IDs
          $recurring_contribution_id = $contribution_id = '';
          if (isset($contribution_recur['id'])) {
            $recurring_contribution_id = $contribution_recur['id'];
          }
          elseif (isset($result_values['sepa_mandate'])) {
            $mandate = reset($result_values['sepa_mandate']);
            if ($mandate['entity_table'] == 'civicrm_contribution_recur') {
              $recurring_contribution_id = (int) $mandate['entity_id'];
            }
          }
          if (isset($contribution['id'])) {
            $contribution_id = $contribution['id'];
          }

          // run the call
          civicrm_api3(trim($pp_entity), trim($pp_action), [
            'membership_id' => $membership['id'],
            'contact_id' => $contact_id,
            'organization_id' => isset($organisation_id) ? $organisation_id : '',
            'contribution_id' => $contribution_id,
            'recurring_contribution_id' => $recurring_contribution_id,
          ]);

          // refresh membership data
          $result_values['membership'] = civicrm_api3('Membership', 'getsingle', ['id' => $membership['id']]);
        }
        catch (CRM_Core_Exception $exception) {
          // TODO: more error handling?
          Civi::log()->warning(
              sprintf(
                'Twingle membership postprocessing call %s.%s has failed: %s',
                $pp_entity,
                $pp_action,
                $exception->getMessage()
              )
          );
          throw new BaseException(
            E::ts('Twingle membership postprocessing call has failed, see log for more information'),
            NULL,
            $exception
          );
        }
      }
    }

    $result = civicrm_api3_create_success($result_values);
  }
  catch (Exception $exception) {
    $result = civicrm_api3_create_error($exception->getMessage());
  }

  return $result;
}
