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
  public const ALLOWED_PRODUCT_ATTRIBUTES = [
    'id',
    'name',
    'internal_id',
    'price',
    'count',
    'total_value',
  ];

  /**
   * @phpstan-var array<string, mixed>
   */
  protected array $params;

  protected CRM_Twingle_Profile $profile;

  /**
   * @phpstan-var array<string, mixed>
   */
  protected array $customFieldValues;

  /**
   * @phpstan-var array<string, mixed>
   */
  protected array $resultValues = [];

  /**
   * @phpstan-param array{
   *   project_id: string,
   * } $params
   */
  public function __construct(array $params) {
    $this->params = $params;
    $this->profile = CRM_Twingle_Profile::getProfileForProject($params['project_id']);
    $this->setCustomFieldValues();
  }

  public function getProfile(): CRM_Twingle_Profile {
    return $this->profile;
  }

  public function getResultValues(): array {
    return $this->resultValues;
  }

  public function getResultValue(string $key) {
    return $this->resultValues[$key] ?? NULL;
  }

  public function setResultValue(string $key, $value): void {
    $this->resultValues[$key] = $value;
  }

  /**
   * @throws \CRM_Core_Exception
   *   When invalid parameters have been submitted.
   */
  // phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
  public function validateSubmission(): void {
  // phpcs:enable
    // Do not process an already existing contribution with the given
    // transaction ID.
    $existing_contribution = civicrm_api3('Contribution', 'get', [
      'trxn_id' => $this->profile->getTransactionID($this->params['trx_id']),
    ]);
    $existing_contribution_recur = civicrm_api3('ContributionRecur', 'get', [
      'trxn_id' => $this->profile->getTransactionID($this->params['trx_id']),
    ]);
    if ($existing_contribution['count'] > 0 || $existing_contribution_recur['count'] > 0) {
      throw new CRM_Core_Exception(
        E::ts('Contribution with the given transaction ID already exists.'),
        'api_error'
      );
    }

    // Validate donation rhythm.
    if (!in_array($this->params['donation_rhythm'], [
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
    $payment_instrument_id = $this->profile->getAttribute('pi_' . $this->params['payment_method'], '');
    if ('' === $payment_instrument_id) {
      throw new CRM_Core_Exception(
        E::ts('Payment method could not be matched to existing payment instrument.'),
        'invalid_format'
      );
    }
    $this->params['payment_instrument_id'] = $payment_instrument_id;

    // Validate date for parameter "confirmed_at".
    if (FALSE === DateTime::createFromFormat('YmdHis', $this->params['confirmed_at'])) {
      throw new CRM_Core_Exception(
        E::ts('Invalid date for parameter "confirmed_at".'),
        'invalid_format'
      );
    }

    // Validate date for parameter "user_birthdate".
    if (
      !empty($this->params['user_birthdate'])
      && FALSE === DateTime::createFromFormat('Ymd', $this->params['user_birthdate'])
    ) {
      throw new CRM_Core_Exception(
        E::ts('Invalid date for parameter "user_birthdate".'),
        'invalid_format'
      );
    }

    // Get the gender ID defined within the profile, or return an error if none
    // matches (i.e. an unknown gender was submitted).
    if (is_string($this->params['user_gender'])) {
      $gender_id = $this->profile->getAttribute('gender_' . $this->params['user_gender']);
      if (!is_numeric($gender_id)) {
        throw new CRM_Core_Exception(
          E::ts('Gender could not be matched to existing gender.'),
          'invalid_format'
        );
      }
      $this->params['gender_id'] = $gender_id;
    }

    // Validate custom fields parameter, if given.
    if (isset($this->params['custom_fields'])) {
      if (is_string($this->params['custom_fields'])) {
        $this->params['custom_fields'] = json_decode($this->params['custom_fields'], TRUE);
      }
      if (!is_array($this->params['custom_fields'])) {
        throw new CRM_Core_Exception(
          E::ts('Invalid format for custom fields.'),
          'invalid_format'
        );
      }
    }

    // Validate products
    if (!empty($this->params['products']) && $this->profile->isShopEnabled()) {
      if (is_string($this->params['products'])) {
        $products = json_decode($this->params['products'], TRUE);
        $this->params['products'] = array_map(
          function($product) {
            return array_intersect_key($product, array_flip(self::ALLOWED_PRODUCT_ATTRIBUTES));
          },
          $products
        );
      }
      if (!is_array($this->params['products'])) {
        throw new CRM_Core_Exception(
          E::ts('Invalid format for products.'),
          'invalid_format'
        );
      }
    }

    // Validate campaign_id, if given.
    if (isset($this->params['campaign_id'])) {
      // Check whether campaign_id is a numeric string and cast it to an integer.
      if (is_numeric($this->params['campaign_id'])) {
        $this->params['campaign_id'] = intval($this->params['campaign_id']);
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
          ['id' => $this->params['campaign_id']]
        );
      }
      catch (CRM_Core_Exception $e) {
        unset($this->params['campaign_id']);
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

    if ($existing_relationship['count'] === 0) {
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
    if (isset($entity_data['campaign_id'])) {
      unset($entity_data['campaign_id']);
    }

    // then: check if campaign should be set it this context
    $enabled_contexts = $profile->getAttribute('campaign_targets');
    if ($enabled_contexts === NULL || !is_array($enabled_contexts)) {
      // backward compatibility:
      $enabled_contexts = ['contribution', 'contact'];
    }
    if (in_array($context, $enabled_contexts, TRUE)) {
      // use the submitted campaign if set
      if (is_numeric($submission['campaign_id'] ?? NULL)) {
        $entity_data['campaign_id'] = $submission['campaign_id'];
      }
      // otherwise use the profile's
      elseif (is_numeric($campaign = $profile->getAttribute('campaign'))) {
        $entity_data['campaign_id'] = $campaign;
      }
    }
  }

  /**
   * Extract custom field values using the profile's mapping of Twingle fields to CiviCRM custom fields.
   *
   * @phpstan-param array{custom_fields: array<string, mixed>} $params
   *
   * @phpstan-return array<string, mixed>
   */
  public function getCustomFieldValues(): array {
    return $this->customFieldValues;
  }

  protected function setCustomFieldValues(): void {
    $this->customFieldValues = [];
    if (is_array($this->params['custom_fields'] ?? NULL)) {
      $custom_field_mapping = $this->profile->getCustomFieldMapping();

      // Make all params available for custom field mapping
      $allowed_params = [];
      _civicrm_api3_twingle_donation_Submit_spec($allowed_params);
      $this->params['custom_fields'] += array_intersect_key($this->params, $custom_field_mapping, $allowed_params);

      foreach ($this->params['custom_fields'] as $twingle_field => $value) {
        if (isset($custom_field_mapping[$twingle_field])) {
          // Get custom field definition to store values by entity the field
          // extends.
          $custom_field_id = substr($custom_field_mapping[$twingle_field], strlen('custom_'));
          /** @phpstan-var array{"api.CustomGroup.getsingle": array<string, mixed>} $custom_field */
          $custom_field = civicrm_api3('CustomField', 'getsingle', [
            'id' => $custom_field_id,
            // Chain a CustomGroup.getsingle API call.
            'api.CustomGroup.getsingle' => [],
          ]);
          $entity = $custom_field['api.CustomGroup.getsingle']['extends'];
          $this->customFieldValues[$entity][$custom_field_mapping[$twingle_field]] = $value;
        }
      }
    }
  }

  protected function prepareAddressParams(): array {
    foreach ([
      'user_street' => 'street_address',
      'user_postal_code' => 'postal_code',
      'user_city' => 'city',
      'user_country' => 'country',
    ] as $address_param => $address_component) {
      if (isset($this->params[$address_param]) && '' !== $this->params[$address_param]) {
        $this->params[$address_component] = $this->params[$address_param];
        unset($this->params[$address_param]);
      }
    }

    // Remove address data when any address component that is configured as
    // required is missing.
    // See https://github.com/systopia/de.systopia.twingle/issues/47
    foreach ($this->profile->getAttribute('required_address_components', []) as $required_address_component) {
      if (empty($this->params[$required_address_component])) {
        foreach ([
          'street_address',
          'postal_code',
          'city',
          'country',
        ] as $address_param) {
          unset($this->params[$address_param]);
        }
        break;
      }
    }

    // Add configured location type to parameters.
    $this->params['location_type_id'] = (int) $this->profile->getAttribute('location_type_id');

    // Exclude address for now when retrieving/creating the individual contact
    // as we are checking organization address first and share it with the
    // individual.
    $submitted_address = [];
    foreach ([
      'street_address',
      'postal_code',
      'city',
      'country',
      'location_type_id',
    ] as $address_component) {
      if (!empty($this->params[$address_component])) {
        $submitted_address[$address_component] = $this->params[$address_component];
        unset($this->params[$address_component]);
      }
    }
    return $submitted_address;
  }

  protected function prepareContactData(): array {
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
      if (!empty($this->params[$contact_param])) {
        $contact_data[$contact_component] = $this->params[$contact_param];
      }
    }

    // Get the prefix ID defined within the profile
    if (
      isset($this->params['user_gender'])
      && is_numeric($prefix_id = $this->profile->getAttribute('prefix_' . $this->params['user_gender']))
    ) {
      $contact_data['prefix_id'] = $prefix_id;
    }

    // Add custom field values.
    if (isset($this->customFieldValues['Contact'])) {
      $contact_data += $this->customFieldValues['Contact'];
    }
    if (isset($this->customFieldValues['Individual'])) {
      $contact_data += $this->customFieldValues['Individual'];
    }

    return $contact_data;
  }

  // phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
  public function handleContacts(): void {
  // phpcs:enable
    $params = $this->params;
    $profile = $this->profile;
    $customFieldValues = $this->getCustomFieldValues();
    $contact_id = $this->getResultValue('contact');

    if ((bool) $params['is_anonymous']) {
      // Retrieve the ID of the contact to use for anonymous donations defined
      // within the profile
      $contact_id = civicrm_api3('Contact', 'getsingle', [
        'id' => $profile->getAttribute('anonymous_contact_id'),
      ])['id'];
    }
    else {
      // Remove parameter "id".
      if (isset($params['id'])) {
        unset($params['id']);
      }

      // Exclude address for now when retrieving/creating the individual contact
      // as we are checking organisation address first and share it with the
      // individual.
      $submitted_address = $this->prepareAddressParams($params, $profile);

      // Prepare parameter mapping for organisation.
      if (is_string($params['user_company']) && '' !== $params['user_company']) {
        $params['organization_name'] = $params['user_company'];
        unset($params['user_company']);
      }

      // Get the ID of the contact matching the given contact data, or create a
      // new contact if none exists for the given contact data.
      $contact_data = $this->prepareContactData();

      // Organisation lookup.
      if (is_string($params['organization_name'] ?? NULL) && '' !== $params['organization_name']) {
        $organisation_data = [
          'organization_name' => $params['organization_name'],
        ];

        // Add custom field values.
        if (isset($customFieldValues['Organization'])) {
          $organisation_data += $customFieldValues['Organization'];
        }

        if ([] !== $submitted_address) {
          $organisation_data += $submitted_address;
          // Use configured location type for organisation address.
          $organisation_data['location_type_id'] = (int) $profile->getAttribute('location_type_id_organisation');
        }
        if (!is_int($organisation_id = self::getContact(
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

      if (!is_int($contact_id = self::getContact(
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
          \Civi\Api4\Note::create(FALSE)
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
        && self::shareWorkAddress(
          $contact_id,
          $organisation_id,
          (int) $profile->getAttribute('location_type_id_organisation')
        )
      );

      // Create employer relationship between organization and individual.
      if (isset($organisation_id)) {
        self::updateEmployerRelation($contact_id, $organisation_id);
      }
    }

    $this->setResultValue('contact', (int) $contact_id);
    if (isset($organisation_id)) {
      $this->setResultValue('organization', $organisation_id);
    }
  }

  // phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
  public function handleGroups(): void {
  // phpcs:enable
    $params = $this->params;
    $profile = $this->profile;
    $resultValues = &$this->resultValues;
    $contact_id = $this->getResultValue('contact');

    // If usage of double opt-in is selected, use MailingEventSubscribe.create
    // to add contact to newsletter groups defined in the profile
    $this->setResultValue(
      'newsletter_double_opt_in',
      (bool) $profile->getAttribute('newsletter_double_opt_in') ? 'true' : 'false'
    );
    if (
      (bool) $profile->getAttribute('newsletter_double_opt_in')
      && (bool) ($params['newsletter'] ?? FALSE)
      && is_array($groups = $profile->getAttribute('newsletter_groups'))
    ) {
      // TODO: Ensure the values being integers.
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
            ],
            )['visibility'] === 'Public Pages';
        if (!in_array($group_id, $group_memberships, FALSE) && $is_public_group) {
          $result = civicrm_api3(
            'MailingEventSubscribe',
            'create',
            [
              'email' => $params['user_email'],
              'group_id' => (int) $group_id,
              'contact_id' => $contact_id,
            ]
          );
          $subscription = reset($result['values']);
          $subscription['group_id'] = $group_id;
          $resultValues['newsletter_subscriptions'][] = $subscription;
        }
        elseif ($is_public_group) {
          $resultValues['newsletter_group_ids'][] = $group_id;
        }
      }
    }
    // If requested, add contact to newsletter groups defined in the profile.
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
        $resultValues['newsletter_group_ids'][] = $group_id;
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

        $resultValues['postinfo'][] = $group_id;
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
        $resultValues['donation_receipt_group_ids'][] = $group_id;
      }
    }
  }

  // phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
  public function handleTransaction(): void {
  // phpcs:enable
    $contactId = $this->getResultValue('contact');
    $profile = $this->profile;
    $params = $this->params;
    $customFieldValues = $this->getCustomFieldValues();

    // Create contribution or SEPA mandate. Those attributes are valid for both,
    // single and recurring contributions.
    $contribution_data = [
      'contact_id' => ($contactId),
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
    if (isset($customFieldValues['Contribution'])) {
      $contribution_data += $customFieldValues['Contribution'];
    }

    // set campaign, subject to configuration
    self::setCampaign($contribution_data, 'contribution', $params, $profile);

    if (NULL !== ($contribution_source = $profile->getAttribute('contribution_source'))) {
      $contribution_data['source'] = $contribution_source;
    }

    if (
      self::civiSepaEnabled()
      && $contribution_data['payment_instrument_id'] === 'sepa'
    ) {
      // If CiviSEPA is installed and the financial type is a CiviSEPA-one,
      // create SEPA mandate (and recurring contribution, using "createfull" API
      // action).
      self::createSepaMandate($contribution_data);
    }
    else {
      // Set financial type depending on donation rhythm. This applies for
      // initial recurring contributions and subsequent single contributions.
      if ($params['donation_rhythm'] !== 'one_time') {
        $contribution_data['financial_type_id'] = $profile->getAttribute('financial_type_id_recur');
      }
      else {
        $contribution_data['financial_type_id'] = $profile->getAttribute('financial_type_id');
      }

      // Create (recurring) contribution.
      // Those will have a donation_rhythm different from "one_time" and no
      // parent_trx_id set.
      if (
        $params['donation_rhythm'] !== 'one_time'
        && empty($params['parent_trx_id'])
      ) {
        $this->createRecurringContribution($contribution_data);
      }

      /** @phpstan-var bool $useBookingDate */
      $useBookingDate = $profile->getAttribute('use_booking_date') ?? FALSE;
      $bookingDate = isset($params['booked_at'])
        ? date_create_from_format('YmdHis', $params['booked_at'])
        : FALSE;

      $contribution_data += [
        'contribution_status_id' => $profile->getAttribute(
          "pi_{$params['payment_method']}_status",
          CRM_Twingle_Submission::CONTRIBUTION_STATUS_COMPLETED
        ),
        'receive_date' => $useBookingDate && FALSE !== $bookingDate ? $params['booked_at'] : $params['confirmed_at'],
      ];

      // Assign to recurring contribution.
      if (!empty($params['parent_trx_id'])) {
        try {
          /** @phpstan-var array<string, mixed> $parent_contribution */
          $parent_contribution = civicrm_api3('ContributionRecur', 'getsingle', [
            'trxn_id' => $profile->getTransactionID($params['parent_trx_id']),
          ]);
          $contribution_data['contribution_recur_id'] = $parent_contribution['id'];
        }
        catch (CRM_Core_Exception $exception) {
          $this->setResultValue(
            'parent_contribution',
            E::ts('Could not find recurring contribution with given parent transaction ID.')
          );
        }
      }

      $contribution = civicrm_api3('Contribution', 'create', $contribution_data);
      /** @phpstan-var array{'values': array<int, array<mixed>>, 'is_error'?: string} $contribution */
      if ((bool) ($contribution['is_error'] ?? FALSE)) {
        throw new CRM_Core_Exception(
          E::ts('Could not create contribution'),
          'api_error'
        );
      }
      $contribution = reset($contribution['values']);
      /** @phpstan-var array{'id': int} $contribution */
      $this->setResultValue('contribution', $contribution);

      // Add notes to the contribution.
      /** @phpstan-var array<string> $contribution_note_mappings */
      $contribution_note_mappings = $profile->getAttribute('map_as_contribution_notes', []);
      foreach (['purpose', 'remarks'] as $target) {
        if (
          in_array($target, $contribution_note_mappings, TRUE)
          && isset($params[$target])
          && '' !== $params[$target]
        ) {
          \Civi\Api4\Note::create(FALSE)
            ->addValue('entity_table', 'civicrm_contribution')
            ->addValue('entity_id', $contribution['id'])
            ->addValue('note', $params[$target])
            ->execute();
        }
      }

      // Add products as line items to the contribution
      if (!empty($params['products']) && $profile->isShopEnabled()) {
        $line_items = self::createLineItems($this->getResultValues(), $params, $profile);
        $resultContribution = $this->getResultValue('contribution');
        $resultContribution['line_items'] = $line_items;
        $this->setResultValue('contribution', $resultContribution);
      }
    }
  }

  // phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
  protected function createSepaMandate(array $contribution_data): void {
  // phpcs:enable
    $profile = $this->getProfile();
    $params = $this->params;
    $custom_fields = $this->getCustomFieldValues();

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
        'type' => ($params['donation_rhythm'] === 'one_time' ? 'OOFF' : 'RCUR'),
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
      + self::getFrequencyMapping($params['donation_rhythm']);
    // Add custom field values.
    if (isset($custom_fields['ContributionRecur'])) {
      $mandate_data += $custom_fields['ContributionRecur'];
    }
    if (NULL !== ($mandate_source = $profile->getAttribute('contribution_source'))) {
      $mandate_data['source'] = $mandate_source;
    }

    // Add cycle day for recurring contributions.
    if ($params['donation_rhythm'] !== 'one_time') {
      $mandate_data['cycle_day'] = self::getSEPACycleDay($params['confirmed_at'], $creditor_id);
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
    self::setCampaign($mandate_data, 'mandate', $params, $profile);

    // Create the mandate.
    $mandate = civicrm_api3('SepaMandate', 'createfull', $mandate_data);

    $this->setResultValue('sepa_mandate', reset($mandate['values']));

    // Add contribution data to result_values for later use
    $contribution_id = $this->getResultValue('sepa_mandate')['entity_id'];
    if ($contribution_id) {
      $contribution = civicrm_api3(
        'Contribution',
        'getsingle',
        ['id' => $contribution_id]
      );
      $this->setResultValue('contribution', $contribution);
    }
    else {
      $mandate_id = $this->getResultValue('sepa_mandate')['id'];
      $message = E::LONG_NAME . ": could not find contribution for sepa mandate $mandate_id";
      throw new CRM_Core_Exception($message, 'api_error');
    }

    // Add products as line items to the contribution
    if (!empty($params['products']) && $profile->isShopEnabled()) {
      $line_items = self::createLineItems($this->getResultValues(), $params, $profile);
      $resultContribution = $this->getResultValue('contribution');
      $resultContribution['line_items'] = $line_items;
      $this->setResultValue('contribution', $resultContribution);
    }
  }

  protected function createRecurringContribution(
    array &$contribution_data,
  ): void {
    $params = $this->params;
    $custom_fields = $this->getCustomFieldValues();
    $profile = $this->getProfile();

    // Create recurring contribution first.
    $contribution_recur_data =
      $contribution_data
      + [
        'contribution_status_id' => 'Pending',
        // TODO: twingle might be sending a different date eventually or use "booking_date".
        'start_date' => $params['confirmed_at'],
      ]
      + self::getFrequencyMapping($params['donation_rhythm']);

    // Add custom field values.
    if (isset($custom_fields['ContributionRecur'])) {
      $contribution_recur_data += $custom_fields['ContributionRecur'];
      $contribution_data += $custom_fields['ContributionRecur'];
    }

    // set campaign, subject to configuration
    self::setCampaign($contribution_data, 'recurring', $params, $profile);

    /** @phpstan-var array<string, mixed> $contribution_recur */
    $contribution_recur = civicrm_api3('ContributionRecur', 'create', $contribution_recur_data);
    if ((bool) $contribution_recur['is_error']) {
      throw new CRM_Core_Exception(
        E::ts('Could not create recurring contribution.'),
        'api_error'
      );
    }
    $contribution_data['contribution_recur_id'] = $contribution_recur['id'];
    $contribution_data['financial_type_id'] = $contribution_recur_data['financial_type_id'];
  }

  /**
   * @param $values
   *   Processed data
   * @param $submission
   *   Submission data
   * @param $profile
   *   The twingle profile used
   *
   * @throws \CRM_Core_Exception
   * @throws \CRM_Core_Exception
   * @throws \Civi\Twingle\Shop\Exceptions\LineItemException
   */
  public static function createLineItems($values, $submission, $profile): array {
    $line_items = [];
    $sum_line_items = 0;

    $contribution_id = $values['contribution']['id'];
    if (empty($contribution_id)) {
      throw new LineItemException(
        'Could not find contribution id for line item assignment.',
        LineItemException::ERROR_CODE_CONTRIBUTION_NOT_FOUND
      );
    }

    foreach ($submission['products'] as $product) {

      $line_item_data = [
        'entity_table' => 'civicrm_contribution',
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
        $price_field = CRM_Twingle_BAO_TwingleProduct::findByExternalId($product['id']);
      }
      catch (Exception $e) {
        Civi::log()->error(E::LONG_NAME .
          ': An error occurred when searching for TwingleShop with the external ID ' .
          $product['id'], ['exception' => $e]);
        $price_field = NULL;
      }
      // If found, use the financial type and price field id from the price field
      if ($price_field) {

        // Log warning if price is not variable and differs from the submission
        if ($price_field->price !== NULL && (int) $price_field->price !== (int) $product['price']) {
          Civi::log()->warning(E::LONG_NAME .
            ': Price for product ' . $product['name'] . ' differs from the PriceField. ' .
            'Using the price from the submission.',
            [
              'price_field' => $price_field->price,
              'submission' => $product['price'],
            ]
          );
        }

        // Log warning if name differs from the submission
        if ($price_field->name !== $product['name']) {
          Civi::log()->warning(E::LONG_NAME .
            ': Name for product ' . $product['name'] . ' differs from the PriceField ' .
            'Using the name from the submission.',
            [
              'price_field' => $price_field->name,
              'submission' => $product['name'],
            ]
          );
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
        throw new CRM_Core_Exception(
          E::ts("Could not create line item for product '%1'", [1 => $line_item_name]),
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
        'entity_table' => 'civicrm_contribution',
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
        throw new CRM_Core_Exception(
          E::ts('Could not create line item for donation'),
          'api_error'
        );
      }

      $line_items[] = array_pop($donation_line_item['values']);
    }

    return $line_items;
  }

  // phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
  public function handleMembership(): void {
  // phpcs:enable
    $params = $this->params;
    $profile = $this->profile;
    $contactId = $this->getResultValue('contact');

    // CHECK whether a membership should be created (based on profile settings and data provided)
    if ($params['donation_rhythm'] === 'one_time') {
      // membership creation based on one-off contributions
      $membership_type_id = $profile->getAttribute('membership_type_id');
    }
    else {
      // membership creation based on recurring contributions
      if (empty($params['parent_trx_id'])) {
        // this is the initial payment
        $membership_type_id = $profile->getAttribute('membership_type_id_recur');
      }
    }

    if (!isset($membership_type_id)) {
      return;
    }

    // CREATE the membership
    $membership_data = [
      'contact_id'         => $contactId,
      'membership_type_id' => $membership_type_id,
    ];
    // set campaign, subject to configuration
    self::setCampaign($membership_data, 'membership', $params, $profile);
    // set source
    if (!empty($membership_source = $profile->getAttribute('contribution_source'))) {
      $membership_data['source'] = $membership_source;
    }

    $membership = civicrm_api3('Membership', 'create', $membership_data);
    $this->setResultValue('membership', $membership);

    // call the postprocess API
    if ('' !== ($postprocess_call = $profile->getAttribute('membership_postprocess_call', ''))) {
      /** @var string $postprocess_call */
      [$pp_entity, $pp_action] = explode('.', $postprocess_call, 2);
      try {
        // gather the contribution IDs
        if (NULL !== $this->getResultValue('contribution_recur_id')) {
          $recurring_contribution_id = $this->getResultValue('contribution_recur_id');
        }
        elseif (NULL !== $this->getResultValue('sepa_mandate')) {
          $mandate = reset($this->getResultValue('sepa_mandate'));
          if ($mandate['entity_table'] === 'civicrm_contribution_recur') {
            $recurring_contribution_id = (int) $mandate['entity_id'];
          }
        }

        // run the call
        civicrm_api3(trim($pp_entity), trim($pp_action), [
          'membership_id' => $membership['id'],
          'contact_id' => $contactId,
          'organization_id' => $this->getResultValue('organization') ?? '',
          'contribution_id' => $this->getResultValue('contribution')['id'] ?? '',
          'recurring_contribution_id' => $recurring_contribution_id ?? '',
        ]);

        // refresh membership data
        $this->setResultValue('membership', civicrm_api3('Membership', 'getsingle', ['id' => $membership['id']]));
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

}
