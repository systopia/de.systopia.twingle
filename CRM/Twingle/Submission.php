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
use Civi\Twingle\Shop\Exceptions\LineItemException;
use Civi\Twingle\Shop\BAO\TwingleProduct;

class CRM_Twingle_Submission {

  /**
   * The default ID of the "Work" location type.
   */
  public const LOCATION_TYPE_ID_WORK = 2;

  /**
   * The option value name of the group type for newsletter subscribers.
   */
  public const GROUP_TYPE_NEWSLETTER = 'Mailing List';

  /**
   * The option value for the contribution type for completed contributions.
   */
  public const CONTRIBUTION_STATUS_COMPLETED = 'Completed';

  /**
   * The default ID of the "Employer of" relationship type.
   */
  public const EMPLOYER_RELATIONSHIP_TYPE_ID = 5;

  /**
   * List of allowed product attributes.
   */
  const ALLOWED_PRODUCT_ATTRIBUTES = [
    'id',
    'name',
    'internal_id',
    'price',
    'count',
    'total_value',
  ];

  /**
   * @param array &$params
   *   A reference to the parameters array of the submission.
   *
   * @param \CRM_Twingle_Profile $profile
   *   The Twingle profile to use for validation, defaults to the default
   *   profile.
   *
   * @throws \CRM_Core_Exception
   *   When invalid parameters have been submitted.
   */
  public static function validateSubmission(&$params, $profile = NULL): void {
    if (!isset($profile)) {
      $profile = CRM_Twingle_Profile::createDefaultProfile();
    }

    // Validate donation rhythm.
    if (!in_array($params['donation_rhythm'], [
      'one_time',
      'halfyearly',
      'quarterly',
      'yearly',
      'monthly',
    ], TRUE)) {
      throw new CRM_Core_Exception(
        E::ts('Invalid donation rhythm.'),
        'invalid_format'
      );
    }

    // Get the payment instrument defined within the profile, or return an error
    // if none matches (i.e. an unknown payment method was submitted).
    $payment_instrument_id = $profile->getAttribute('pi_' . $params['payment_method'], '');
    if ('' === $payment_instrument_id) {
      throw new CRM_Core_Exception(
        E::ts('Payment method could not be matched to existing payment instrument.'),
        'invalid_format'
      );
    }
    $params['payment_instrument_id'] = $payment_instrument_id;

    // Validate date for parameter "confirmed_at".
    if (FALSE === DateTime::createFromFormat('YmdHis', $params['confirmed_at'])) {
      throw new CRM_Core_Exception(
        E::ts('Invalid date for parameter "confirmed_at".'),
        'invalid_format'
      );
    }

    // Validate date for parameter "user_birthdate".
    if (!empty($params['user_birthdate']) && FALSE === DateTime::createFromFormat('Ymd', $params['user_birthdate'])) {
      throw new CRM_Core_Exception(
        E::ts('Invalid date for parameter "user_birthdate".'),
        'invalid_format'
      );
    }

    // Get the gender ID defined within the profile, or return an error if none
    // matches (i.e. an unknown gender was submitted).
    if (is_string($params['user_gender'])) {
      $gender_id = $profile->getAttribute('gender_' . $params['user_gender']);
      if (!is_numeric($gender_id)) {
        throw new CRM_Core_Exception(
          E::ts('Gender could not be matched to existing gender.'),
          'invalid_format'
        );
      }
      $params['gender_id'] = $gender_id;
    }

    // Validate custom fields parameter, if given.
    if (isset($params['custom_fields'])) {
      if (is_string($params['custom_fields'])) {
        $params['custom_fields'] = json_decode($params['custom_fields'], TRUE);
      }
      if (!is_array($params['custom_fields'])) {
        throw new CRM_Core_Exception(
          E::ts('Invalid format for custom fields.'),
          'invalid_format'
        );
      }
    }

    // Validate products
    if (!empty($params['products']) && $profile->isShopEnabled()) {
      if (is_string($params['products'])) {
        $products = json_decode($params['products'], TRUE);
        $params['products'] = array_map(function ($product) {
            return array_intersect_key($product, array_flip(self::ALLOWED_PRODUCT_ATTRIBUTES));
          }, $products);
      }
      if (!is_array($params['products'])) {
        throw new CiviCRM_API3_Exception(
          E::ts('Invalid format for products.'),
          'invalid_format'
        );
      }
    }

    // Validate campaign_id, if given.
    if (isset($params['campaign_id'])) {
      // Check whether campaign_id is a numeric string and cast it to an integer.
      if (is_numeric($params['campaign_id'])) {
        $params['campaign_id'] = intval($params['campaign_id']);
      }
      else {
        throw new CRM_Core_Exception(
          E::ts('campaign_id must be a numeric string. '),
          'invalid_format'
        );
      }
      // Check whether given campaign_id exists and if not, unset the parameter.
      try {
        civicrm_api3(
          'Campaign',
          'getsingle',
          ['id' => $params['campaign_id']]
        );
      }
      catch (CRM_Core_Exception $e) {
        unset($params['campaign_id']);
      }
    }
  }

  /**
   * Retrieves the contact matching the given contact data or creates a new
   * contact.
   *
   * @param string $contact_type
   *   The contact type to look for/to create.
   * @param array<string, mixed> $contact_data
   *   Data to use for contact lookup/to create a contact with.
   * @param CRM_Twingle_Profile $profile
   *   Profile used for this process
   * @param array<string, mixed> $submission
   *   Submission data
   *
   * @return int|NULL
   *   The ID of the matching/created contact, or NULL if no matching contact
   *   was found and no new contact could be created.
   * @throws \CRM_Core_Exception
   *   When invalid data was given.
   */
  public static function getContact(
    string $contact_type,
    array $contact_data,
    CRM_Twingle_Profile $profile,
    array $submission = []
  ) {
    // If no parameters are given, do nothing.
    if ([] === $contact_data) {
      return NULL;
    }

    // add xcm profile
    $xcm_profile = $profile->getAttribute('xcm_profile');
    if (isset($xcm_profile) && '' !== $xcm_profile) {
      $contact_data['xcm_profile'] = $xcm_profile;
    }

    // add campaign, see issue #17
    CRM_Twingle_Submission::setCampaign($contact_data, 'contact', $submission, $profile);

    // Prepare values: country.
    if (isset($contact_data['country'])) {
      if (is_numeric($contact_data['country'])) {
        // If a country ID is given, update the parameters.
        $contact_data['country_id'] = $contact_data['country'];
        unset($contact_data['country']);
      }
      else {
        // Look up the country depending on the given ISO code.
        $country = civicrm_api3('Country', 'get', ['iso_code' => $contact_data['country']]);
        if (isset($country['id'])) {
          $contact_data['country_id'] = $country['id'];
          unset($contact_data['country']);
        }
        else {
          throw new \CRM_Core_Exception(
            E::ts('Unknown country %1.', [1 => $contact_data['country']]),
            'invalid_format'
          );
        }
      }
    }

    // Prepare values: language.
    if (is_string($contact_data['preferred_language']) && '' !== $contact_data['preferred_language']) {
      $mapping = CRM_Core_I18n_PseudoConstant::longForShortMapping();
      // Override the default mapping for German.
      $mapping['de'] = 'de_DE';
      $contact_data['preferred_language'] = $mapping[$contact_data['preferred_language']];
    }

    // Pass to XCM.
    $contact_data['contact_type'] = $contact_type;
    $contact = civicrm_api3('Contact', 'getorcreate', $contact_data);

    return isset($contact['id']) ? (int) $contact['id'] : NULL;
  }

  /**
   * Shares an organisation's work address, unless the contact already has one.
   *
   * @param int $contact_id
   *   The ID of the contact to share the organisation address with.
   * @param int $organisation_id
   *   The ID of the organisation whose address to share with the contact.
   * @param int $location_type_id
   *   The ID of the location type to use for address lookup.
   *
   * @return boolean
   *   Whether the organisation address has been shared with the contact.
   *
   * @throws \CRM_Core_Exception
   *   When looking up or creating the shared address failed.
   */
  public static function shareWorkAddress(
    int $contact_id,
    int $organisation_id,
    int $location_type_id = self::LOCATION_TYPE_ID_WORK
  ) {
    // Check whether organisation has a WORK address.
    $existing_org_addresses = civicrm_api3('Address', 'get', [
      'contact_id'       => $organisation_id,
      'location_type_id' => $location_type_id,
    ]);
    if ($existing_org_addresses['count'] <= 0) {
      // Organisation does not have a WORK address.
      return FALSE;
    }

    // Check whether contact already has a WORK address.
    $existing_contact_addresses = civicrm_api3('Address', 'get', [
      'contact_id'       => $contact_id,
      'location_type_id' => $location_type_id,
    ]);
    if ($existing_contact_addresses['count'] > 0) {
      // Contact already has a WORK address.
      return FALSE;
    }

    // Create a shared address.
    $address = reset($existing_org_addresses['values']);
    $address['contact_id'] = $contact_id;
    $address['master_id']  = $address['id'];
    unset($address['id']);
    civicrm_api3('Address', 'create', $address);
    return TRUE;
  }

  /**
   * Updates or creates an employer relationship between contact and
   * organisation.
   *
   * @param int $contact_id
   *   The ID of the employee contact.
   * @param int $organisation_id
   *   The ID of the employer contact.
   *
   * @throws \CRM_Core_Exception
   */
  public static function updateEmployerRelation(int $contact_id, int $organisation_id): void {
    // see if there is already one
    $existing_relationship = civicrm_api3('Relationship', 'get', [
      'relationship_type_id' => self::EMPLOYER_RELATIONSHIP_TYPE_ID,
      'contact_id_a' => $contact_id,
      'contact_id_b' => $organisation_id,
      'is_active' => 1,
    ]);

    if ($existing_relationship['count'] == 0) {
      // There is currently no (active) relationship between these contacts.
      $new_relationship_data = [
        'relationship_type_id' => self::EMPLOYER_RELATIONSHIP_TYPE_ID,
        'contact_id_a' => $contact_id,
        'contact_id_b' => $organisation_id,
        'is_active' => 1,
      ];

      civicrm_api3('Relationship', 'create', $new_relationship_data);
    }
  }

  /**
   * Check whether the CiviSEPA extension is installed and CiviSEPA
   * functionality is activated within the Twingle extension settings.
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public static function civiSepaEnabled() {
    $sepa_extension = civicrm_api3('Extension', 'get', [
      'full_name' => 'org.project60.sepa',
      'is_active' => 1,
    ]);
    return (bool) Civi::settings()->get('twingle_use_sepa')
      && $sepa_extension['count'] >= 0;
  }

  /**
   * Retrieves recurring contribution frequency attributes for a given donation
   * rhythm parameter value, according to a static mapping.
   *
   * @param string $donation_rhythm
   *   The submitted "donation_rhythm" paramter according to the API action
   *   specification.
   *
   * @return array{'frequency_unit'?: string, 'frequency_interval'?: int}
   *   An array with "frequency_unit" and "frequency_interval" keys, to be added
   *   to contribution parameter arrays.
   */
  public static function getFrequencyMapping($donation_rhythm) {
    $mapping = [
      'halfyearly' => [
        'frequency_unit' => 'month',
        'frequency_interval' => 6,
      ],
      'quarterly' => [
        'frequency_unit' => 'month',
        'frequency_interval' => 3,
      ],
      'yearly' => [
        'frequency_unit' => 'month',
        'frequency_interval' => 12,
      ],
      'monthly' => [
        'frequency_unit' => 'month',
        'frequency_interval' => 1,
      ],
      'one_time' => [],
    ];

    return $mapping[$donation_rhythm];
  }

  /**
   * Retrieves the next possible cycle day for a SEPA mandate from a given start
   * date of the mandate, depending on CiviSEPA creditor configuration.
   *
   * @param string $start_date
   *   A string representing a date in the format "Ymd".
   *
   * @param int $creditor_id
   *   The ID of the CiviSEPA creditor to use for determining the cycle day.
   *
   * @return int
   *   The next possible day of this or the next month to start collecting.
   */
  public static function getSEPACycleDay($start_date, $creditor_id): int {
    $buffer_days = (int) CRM_Sepa_Logic_Settings::getSetting('pp_buffer_days');
    $frst_notice_days = (int) CRM_Sepa_Logic_Settings::getSetting('batching.FRST.notice', $creditor_id);
    if (FALSE === ($earliest_rcur_date = strtotime("$start_date + $frst_notice_days days + $buffer_days days"))) {
      throw new BaseException(E::ts('Could not calculate SEPA cycle day from configuration.'));
    }

    // Find the next cycle day
    $cycle_days = CRM_Sepa_Logic_Settings::getListSetting('cycledays', range(1, 28), $creditor_id);
    $earliest_cycle_day = $earliest_rcur_date;
    while (!in_array(date('j', $earliest_cycle_day), $cycle_days, TRUE)) {
      $earliest_cycle_day = strtotime('+ 1 day', $earliest_cycle_day);
    }

    return (int) date('j', $earliest_cycle_day);
  }

  /**
   * Will set the campaign_id to the entity_data set, if the
   * profile is configured to do so. In that case the campaign is taken
   * from the submission data. Should that be empty, the profile's default
   * campaign is used.
   *
   * @param array<string, mixed> $entity_data
   *   the data set where the campaign_id should be set
   * @param string $context
   *   defines the type of the entity_data: one of 'contribution', 'membership','mandate', 'recurring', 'contact'
   * @param array<string, mixed> $submission
   *   the submitted data
   * @param CRM_Twingle_Profile $profile
   *   the twingle profile used
   */
  public static function setCampaign(
    array &$entity_data,
    string $context,
    array $submission,
    CRM_Twingle_Profile $profile
  ): void {
    // first: make sure it's not set from other workflows
    unset($entity_data['campaign_id']);

    // then: check if campaign should be set it this context
    $enabled_contexts = $profile->getAttribute('campaign_targets');
    if ($enabled_contexts === NULL || !is_array($enabled_contexts)) {
      // backward compatibility:
      $enabled_contexts = ['contribution', 'contact'];
    }
    if (in_array($context, $enabled_contexts, TRUE)) {
      // use the submitted campaign if set
      if (is_numeric($submission['campaign_id'])) {
        $entity_data['campaign_id'] = $submission['campaign_id'];
      }
      // otherwise use the profile's
      elseif (is_numeric($campaign = $profile->getAttribute('campaign'))) {
        $entity_data['campaign_id'] = $campaign;
      }
    }
  }

  /**
   * @param $values
   *   Processed data
   * @param $submission
   *   Submission data
   * @param $profile
   *   The twingle profile used
   *
   * @throws \CiviCRM_API3_Exception
   * @throws \CRM_Core_Exception
   * @throws \Civi\Twingle\Shop\Exceptions\LineItemException
   */
  public static function createLineItems($values, $submission, $profile): array {
    $line_items = [];
    $sum_line_items = 0;

    $contribution_id = $values['contribution']['id'];
    if (empty($contribution_id)) {
      throw new LineItemException(
        "Could not find contribution id for line item assignment.",
        LineItemException::ERROR_CODE_CONTRIBUTION_NOT_FOUND
      );
    }

    foreach ($submission['products'] as $product) {

      $line_item_data = [
        'entity_table' => "civicrm_contribution",
        'contribution_id' => $contribution_id,
        'entity_id' => $contribution_id,
        'label' => $product['name'],
        'qty' => $product['count'],
        'unit_price' => $product['price'],
        'line_total' => $product['total_value'],
        'sequential' => 1,
      ];

      // Try to find the TwingleProduct with its corresponding PriceField
      // for this product
      try {
        $price_field = TwingleProduct::findByExternalId($product['id']);
      }
      catch (Exception $e) {
        Civi::log()->error(E::LONG_NAME .
          ": An error occurred when searching for TwingleShop with the external ID " .
          $product['id'], ['exception' => $e]);
        $price_field = NULL;
      }
      // If found, use the financial type and price field id from the price field
      if ($price_field) {

        // Log warning if price is not variable and differs from the submission
        if ($price_field->price !== Null && $price_field->price != (int) $product['price']) {
          Civi::log()->warning(E::LONG_NAME .
            ": Price for product " . $product['name'] . " differs from the PriceField. " .
            "Using the price from the submission.", ['price_field' => $price_field->price, 'submission' => $product['price']]);
        }

        // Log warning if name differs from the submission
        if ($price_field->name != $product['name']) {
          Civi::log()->warning(E::LONG_NAME .
            ": Name for product " . $product['name'] . " differs from the PriceField " .
            "Using the name from the submission.", ['price_field' => $price_field->name, 'submission' => $product['name']]);
        }

        // Set the financial type and price field id
        $line_item_data['financial_type_id'] = $price_field->financial_type_id;
        $line_item_data['price_field_value_id'] = $price_field->getPriceFieldValueId();
        $line_item_data['price_field_id'] = $price_field->price_field_id;
        $line_item_data['description'] = $price_field->description;
      }
      // If not found, use the shops default financial type
      else {
        $financial_type_id = $profile->getAttribute('shop_financial_type', 1);
        $line_item_data['financial_type_id'] = $financial_type_id;
      }

      // Create the line item
      $line_item = civicrm_api3('LineItem', 'create', $line_item_data);

      if (!empty($line_item['is_error'])) {
        $line_item_name = $line_item_data['name'];
        throw new CiviCRM_API3_Exception(
          E::ts("Could not create line item for product '$line_item_name'"),
          'api_error'
        );
      }
      $line_items[] = array_pop($line_item['values']);

      $sum_line_items += $product['total_value'];
    }

    // Create line item for donation part
    $donation_sum = (float) $values['contribution']['total_amount'] - $sum_line_items;
    if ($donation_sum > 0) {
      $donation_financial_type_id = $profile->getAttribute('shop_donation_financial_type', 1);
      $donation_label = civicrm_api3('FinancialType', 'getsingle', [
        'return' => ['name'],
        'id' => $donation_financial_type_id,
      ])['name'];

      $donation_line_item_data = [
        'entity_table' => "civicrm_contribution",
        'contribution_id' => $contribution_id,
        'entity_id' => $contribution_id,
        'label' => $donation_label,
        'qty' => 1,
        'unit_price' => $donation_sum,
        'line_total' => $donation_sum,
        'financial_type_id' => $donation_financial_type_id,
        'sequential' => 1,
      ];

      $donation_line_item = civicrm_api3('LineItem', 'create', $donation_line_item_data);

      if (!empty($donation_line_item['is_error'])) {
        throw new CiviCRM_API3_Exception(
          E::ts("Could not create line item for donation"),
          'api_error'
        );
      }

      $line_items[] = array_pop($donation_line_item['values']);
    }

    return $line_items;
  }
}
