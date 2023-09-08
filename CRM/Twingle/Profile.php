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
use Civi\Twingle\Exceptions\ProfileException as ProfileException;
use Civi\Twingle\Exceptions\ProfileValidationError;

/**
 * Profiles define how incoming submissions from the Twingle API are
 * processed in CiviCRM.
 */
class CRM_Twingle_Profile {

  /**
   * @var int
   *   The id of the profile.
   */
  protected ?int $id;

  /**
   * @var string
   *   The name of the profile.
   */
  protected string $name;

  /**
   * @var array<string, mixed>
   *   The properties of the profile.
   */
  protected $data;

  /**
   * CRM_Twingle_Profile constructor.
   *
   * @param string $name
   *   The name of the profile.
   * @param array<string, mixed> $data
   *   The properties of the profile
   * @param int|NULL $id
   */
  public function __construct($name, $data, $id = NULL) {
    $this->id = $id;
    $this->name = $name;
    $allowed_attributes = self::allowedAttributes();
    $this->data = $data + array_combine(
        $allowed_attributes,
        array_fill(0, count($allowed_attributes), NULL)
      );
  }

  /**
   * Logs (production) access to this profile
   */
  public function logAccess(): void {
    CRM_Core_DAO::executeQuery('
        UPDATE civicrm_twingle_profile
        SET
            last_access = NOW(),
            access_counter = access_counter + 1
        WHERE name = %1', [1 => [$this->name, 'String']]);
  }

  /**
   * Copy this profile by returning a clone with all unique information removed.
   *
   * @return CRM_Twingle_Profile
   */
  public function copy() {
    $copy = clone $this;

    // Remove unique data
    $copy->id = NULL;
    $copy->data['selector'] = NULL;

    // Propose a new name for this profile.
    $profile_name = $this->getName() . '_copy';
    $copy->setName($profile_name);
    return $copy;
  }

  /**
   * Checks whether the profile's selector matches the given project ID.
   *
   * @param string|int $project_id
   *
   * @return bool
   */
  public function matches($project_id) {
    return in_array($project_id, $this->getProjectIds(), TRUE);
  }

  /**
   * Retrieves the profile's configured custom field mapping.
   *
   * @return array<string, string>
   *   The profile's configured custom field mapping
   */
  public function getCustomFieldMapping() {
    $custom_field_mapping = [];
    if ('' !== ($custom_field_definition = $this->getAttribute('custom_field_mapping', ''))) {
      /** @var string $custom_field_definition */
      $custom_field_maps = preg_split(
        '/\r\n|\r|\n/',
        $custom_field_definition,
        -1,
        PREG_SPLIT_NO_EMPTY
      );
      if (FALSE !== $custom_field_maps) {
        foreach ($custom_field_maps as $custom_field_map) {
          [$twingle_field_name, $custom_field_name] = explode('=', $custom_field_map);
          $custom_field_mapping[$twingle_field_name] = $custom_field_name;
        }
      }
    }
    return $custom_field_mapping;
  }

  /**
   * Retrieves all data attributes of the profile.
   *
   * @return array<string, mixed>
   */
  public function getData() {
    return $this->data;
  }

  /**
   * Retrieves the profile id.
   *
   * @return int
   */
  public function getId(): ?int {
    return $this->id;
  }

  /**
   * Set the profile id.
   *
   * @param int $id
   */
  public function setId(int $id): void {
    $this->id = $id;
  }

  /**
   * Retrieves the profile name.
   *
   * @return string
   */
  public function getName(): string {
    return $this->name;
  }

  /**
   * Sets the profile name.
   *
   * @param string $name
   */
  public function setName(string $name): void {
    $this->name = $name;
  }

  /**
   * Is this the default profile?
   *
   * @return bool
   */
  public function is_default(): bool {
    return $this->name === 'default';
  }

  /**
   * Retrieves the profile's project IDs.
   *
   * @return array<string>
   */
  public function getProjectIds(): array {
    return array_map(
      function($project_id) {
        return trim($project_id);
      },
      explode(',', $this->getAttribute('selector') ?? '')
    );
  }

  /**
   * Retrieves an attribute of the profile.
   *
   * @param string $attribute_name
   * @param mixed $default
   *
   * @return mixed | NULL
   */
  public function getAttribute($attribute_name, $default = NULL) {
    return (isset($this->data[$attribute_name]) && $this->data[$attribute_name] !== '')
      ? $this->data[$attribute_name]
      : $default;
  }

  /**
   * Sets an attribute of the profile.
   *
   * @param string $attribute_name
   * @param mixed $value
   *
   * @throws \Civi\Twingle\Exceptions\ProfileException
   *   When the attribute name is not known.
   */
  public function setAttribute($attribute_name, $value): void {
    if (!in_array($attribute_name, self::allowedAttributes(), TRUE)) {
      throw new ProfileException(
        E::ts('Unknown attribute %1.', [1 => $attribute_name]),
      ProfileException::ERROR_CODE_UNKNOWN_PROFILE_ATTRIBUTE
      );
    }
    // TODO: Check if value is acceptable.
    $this->data[$attribute_name] = $value;
  }

  /**
   * Get the CiviCRM transaction ID (to be used in contributions and recurring contributions)
   *
   * @param string $twingle_id Twingle ID
   * @return string CiviCRM transaction ID
   */
  public function getTransactionID(string $twingle_id) {
    $prefix = Civi::settings()->get('twingle_prefix');
    return ($prefix ?? '') . $twingle_id;
  }

  /**
   * Verifies whether the profile is valid (i.e. consistent and not colliding
   * with other profiles).
   *
   * @throws \Civi\Twingle\Exceptions\ProfileValidationError
   * @throws \Civi\Core\Exception\DBQueryException
   *   When the profile could not be successfully validated.
   */
  public function validate(): void {

    // Name cannot be empty
    if ('' === $this->getName()) {
      throw new ProfileValidationError(
        'name',
        E::ts('Profile name cannot be empty.'),
        ProfileValidationError::ERROR_CODE_PROFILE_VALIDATION_FAILED
      );
    }

    // Restrict profile names to alphanumeric characters, space and the underscore.
    if (1 === preg_match('/[^A-Za-z0-9_\s]/', $this->getName())) {
      throw new ProfileValidationError(
        'name',
        E::ts('Only alphanumeric characters, space and the underscore (_) are allowed for profile names.'),
        ProfileValidationError::ERROR_CODE_PROFILE_VALIDATION_FAILED
      );
    }

    // Check if profile name is already used for other profile
    $profile_name_duplicates = array_filter(
      CRM_Twingle_Profile::getProfiles(),
      function($profile) {
        return $profile->getName() == $this->getName() && $this->getId() != $profile->getId();
      });
    if ([] !== $profile_name_duplicates) {
      throw new ProfileValidationError(
        'name',
        E::ts("A profile with the name '%1' already exists.", [1 => $this->getName()]),
        ProfileValidationError::ERROR_CODE_PROFILE_VALIDATION_FAILED
      );
    }

    // Check if project_id is already used in other profile
    $profiles = $this::getProfiles();
    foreach ($profiles as $profile) {
      if ($profile->getId() == $this->getId() || $profile->is_default()) {
        continue;
      };
      $project_ids = $this->getProjectIds();
      $id_duplicates = array_intersect($profile->getProjectIds(), $project_ids);
      if ([] !== $id_duplicates) {
        throw new ProfileValidationError(
          'selector',
          E::ts(
            "Project ID(s) [%1] already used in profile '%2'.",
            [
              1 => implode(', ', $id_duplicates),
              2 => $profile->getName(),
            ]
          ),
          ProfileValidationError::ERROR_CODE_PROFILE_VALIDATION_WARNING
        );
      }
    }

    // Validate custom field mapping.
    $custom_field_mapping = $this->getAttribute('custom_field_mapping');
    if (is_string($custom_field_mapping)) {
      $custom_field_mapping = preg_split('/\r\n|\r|\n/', $custom_field_mapping, -1, PREG_SPLIT_NO_EMPTY);
      $parsing_error = new ProfileValidationError(
        'custom_field_mapping',
        E::ts('Could not parse custom field mapping.'),
        ProfileValidationError::ERROR_CODE_PROFILE_VALIDATION_FAILED
      );
      if (!is_array($custom_field_mapping)) {
        throw $parsing_error;
      }
      foreach ($custom_field_mapping as $custom_field_map) {
        $custom_field_map = explode('=', $custom_field_map);
        if (count($custom_field_map) !== 2) {
          throw $parsing_error;
        }
        [$twingle_field_name, $custom_field_name] = $custom_field_map;
        $custom_field_id = substr($custom_field_name, strlen('custom_'));

        // Check for custom field existence
        try {
          /**
           * @phpstan-var array<string, mixed> $custom_field
           */
          $custom_field = civicrm_api3(
            'CustomField', 'getsingle', ['id' => $custom_field_id]
          );
        }
        catch (CRM_Core_Exception $exception) {
          throw new ProfileValidationError(
            'custom_field_mapping',
            E::ts(
              'Custom field custom_%1 does not exist.',
              [1 => $custom_field_id]
            ),
            ProfileValidationError::ERROR_CODE_PROFILE_VALIDATION_FAILED,
            $exception
          );
        }

        // Only allow custom fields on relevant entities.
        try {
          civicrm_api3('CustomGroup', 'getsingle',
            [
              'id' => $custom_field['custom_group_id'],
              'extends' => [
                'IN' => [
                  'Contact',
                  'Individual',
                  'Organization',
                  'Contribution',
                  'ContributionRecur',
                ],
              ],
            ]);
        }
        catch (CRM_Core_Exception $exception) {
          throw new ProfileValidationError(
            'custom_field_mapping',
            E::ts(
              'Custom field custom_%1 is not in a CustomGroup that extends one of the supported CiviCRM entities.',
              [1 => $custom_field['id']]
            ),
            ProfileValidationError::ERROR_CODE_PROFILE_VALIDATION_FAILED,
            $exception
          );
        }
      }
    }
  }

  /**
   * Persists the profile within the database.
   *
   * @throws \Civi\Twingle\Exceptions\ProfileException
   */
  public function saveProfile(): void {
    try {
      if (isset($this->id)) {
        // existing profile -> just update the config
        CRM_Core_DAO::executeQuery(
          'UPDATE civicrm_twingle_profile SET config = %2, name = %3 WHERE id = %1',
          [
            1 => [$this->id, 'String'],
            2 => [json_encode($this->data), 'String'],
            3 => [$this->name, 'String'],
          ]);
      }
      else {
        // new profile -> add new entry to the DB
        CRM_Core_DAO::executeQuery(
          <<<SQL
          INSERT IGNORE INTO civicrm_twingle_profile(name,config,last_access,access_counter) VALUES (%1, %2, null, 0)
          SQL,
          [
            1 => [$this->name, 'String'],
            2 => [json_encode($this->data), 'String'],
          ]);
      }
    }
    catch (Exception $exception) {
      throw new ProfileException(
        E::ts('Could not save/update profile: %1', [1 => $exception->getMessage()]),
        ProfileException::ERROR_CODE_COULD_NOT_SAVE_PROFILE,
        $exception
      );
    }
  }

  /**
   * Deletes the profile from the database
   *
   * @throws \Civi\Twingle\Exceptions\ProfileException
   */
  public function deleteProfile(): void {
    // Do only reset default profile
    if ($this->getName() == 'default') {
      try {
        $default_profile = CRM_Twingle_Profile::createDefaultProfile();
        $default_profile->setId($this->getId());
        $default_profile->saveProfile();

        // Reset counter
        CRM_Core_DAO::executeQuery(
          'UPDATE civicrm_twingle_profile SET access_counter = 0, last_access = NULL WHERE id = %1',
          [1 => [$this->id, 'Integer']]
        );
      }
      catch (Exception $exception) {
        throw new ProfileException(
          E::ts('Could not reset default profile: %1', [1 => $exception->getMessage()]),
          ProfileException::ERROR_CODE_COULD_NOT_RESET_PROFILE,
          $exception
        );
      }
    }
    else {
      try {
        CRM_Core_DAO::executeQuery(
          'DELETE FROM civicrm_twingle_profile WHERE id = %1',
          [1 => [$this->id, 'Integer']]
        );
      }
      catch (Exception $exception) {
        throw new ProfileException(
          E::ts('Could not delete profile: %1', [1 => $exception->getMessage()]),
          ProfileException::ERROR_CODE_COULD_NOT_DELETE_PROFILE,
          $exception
        );
      }
    }
  }

  /**
   * Returns an array of attributes allowed for a profile.
   *
   * @return array<string>|array<string, bool>
   */
  public static function allowedAttributes(bool $asMetadata = FALSE) {
    $attributes = array_merge(
      [
        'selector' => [
          'label' => E::ts('Project IDs'),
          'required' => TRUE,
        ],
        'xcm_profile' => ['required' => FALSE],
        'location_type_id' => [
          'label' => E::ts('Location type'),
          'required' => TRUE,
        ],
        'location_type_id_organisation' => [
          'label' => E::ts('Location type for organisations'),
          'required' => TRUE,
        ],
        'financial_type_id' => [
          'label' => E::ts('Financial type'),
          'required' => TRUE,
        ],
        'financial_type_id_recur' => [
          'label' => E::ts('Financial type (recurring)'),
          'required' => TRUE,
        ],
        'sepa_creditor_id' => [
          'label' => E::ts('CiviSEPA creditor'),
          'required' => CRM_Twingle_Submission::civiSepaEnabled(),
        ],
        'gender_male' => [
          'label' => E::ts('Gender option for submitted value "male"'),
          'required' => TRUE,
        ],
        'gender_female' => [
          'label' => E::ts('Gender option for submitted value "female"'),
          'required' => TRUE,
        ],
        'gender_other' => [
          'label' => E::ts('Gender option for submitted value "other"'),
          'required' => TRUE,
        ],
        'prefix_male' => [
          'label' => E::ts('Prefix option for submitted value "male"'),
          'required' => TRUE,
        ],
        'prefix_female' => [
          'label' => E::ts('Prefix option for submitted value "female"'),
          'required' => TRUE,
        ],
        'prefix_other' => [
          'label' => E::ts('Prefix option for submitted value "other"'),
          'required' => TRUE,
        ],
        'newsletter_groups' => ['required' => FALSE],
        'postinfo_groups' => ['required' => FALSE],
        'donation_receipt_groups' => ['required' => FALSE],
        'campaign' => ['required' => FALSE],
        'campaign_targets' => ['required' => FALSE],
        'contribution_source' => ['required' => FALSE],
        'custom_field_mapping' => ['required' => FALSE],
        'membership_type_id' => ['required' => FALSE],
        'membership_type_id_recur' => ['required' => FALSE],
        'membership_postprocess_call' => ['required' => FALSE],
        'newsletter_double_opt_in' => ['required' => FALSE],
        'required_address_components' => ['required' => FALSE],
        'map_as_contribution_notes' => ['required' => FALSE],
        'map_as_contact_notes' => ['required' => FALSE],
      ],
      // Add payment methods.
      array_combine(
        array_keys(static::paymentInstruments()),
        array_map(
        function ($value) {
          return [
            'label' => $value,
            'required' => TRUE,
          ];
        },
        static::paymentInstruments()
      )),

      // Add contribution status for all payment methods.
      array_combine(
        array_map(function($attribute) {
          return $attribute . '_status';
        }, array_keys(static::paymentInstruments())),
        array_map(
          function($value) {
            return [
              'label' => $value . ' - ' . E::ts('Contribution Status'),
              'required' => TRUE,
            ];
          },
          static::paymentInstruments()
        )),
    );

    return $asMetadata ? $attributes : array_keys($attributes);
  }

  /**
   * Retrieves a list of supported payment methods.
   *
   * @return array<string, string>
   */
  public static function paymentInstruments(): array {
    return [
      'pi_banktransfer' => E::ts('Bank transfer'),
      'pi_debit_manual' => E::ts('Debit manual'),
      'pi_debit_automatic' => E::ts('Debit automatic'),
      'pi_creditcard' => E::ts('Credit card'),
      'pi_mobilephone_germany' => E::ts('Mobile phone Germany'),
      'pi_paypal' => E::ts('PayPal'),
      'pi_sofortueberweisung' => E::ts('SOFORT Überweisung'),
      'pi_amazonpay' => E::ts('Amazon Pay'),
      'pi_applepay' => E::ts('Apple Pay'),
      'pi_googlepay' => E::ts('Google Pay'),
      'pi_paydirekt' => E::ts('Paydirekt'),
      'pi_twint' => E::ts('Twint'),
      'pi_ideal' => E::ts('iDEAL'),
      'pi_post_finance' => E::ts('Postfinance'),
      'pi_bancontact' => E::ts('Bancontact'),
      'pi_generic' => E::ts('Generic Payment Method'),
    ];
  }

  /**
   * Returns the default profile with "factory" defaults.
   *
   * @param string $name
   *   The profile name. Defaults to "default".
   *
   * @return CRM_Twingle_Profile
   */
  public static function createDefaultProfile($name = 'default') {
    return new CRM_Twingle_Profile($name, [
      'selector'          => NULL,
      'xcm_profile'       => '',
      'location_type_id'  => CRM_Twingle_Submission::LOCATION_TYPE_ID_WORK,
      'location_type_id_organisation' => CRM_Twingle_Submission::LOCATION_TYPE_ID_WORK,
      // "Donation"
      'financial_type_id' => 1,
      // "Donation"
      'financial_type_id_recur' => 1,
      // "EFT"
      'pi_banktransfer' => 5,
      'pi_debit_manual' => NULL,
      // Debit
      'pi_debit_automatic' => 2,
      // "Credit Card"
      'pi_creditcard' => 1,
      'pi_mobilephone_germany' => NULL,
      'pi_paypal' => NULL,
      'pi_sofortueberweisung' => NULL,
      'pi_amazonpay' => NULL,
      'pi_paydirekt' => NULL,
      'pi_applepay' => NULL,
      'pi_googlepay' => NULL,
      'pi_twint' => NULL,
      'pi_ideal' => NULL,
      'pi_post_finance' => NULL,
      'pi_bancontact' => NULL,
      'pi_generic' => NULL,
      'sepa_creditor_id' => NULL,
      'gender_male' => 2,
      'gender_female' => 1,
      'gender_other' => 3,
      'newsletter_groups' => NULL,
      'postinfo_groups' => NULL,
      'donation_receipt_groups' => NULL,
      'campaign' => NULL,
      'campaign_targets' => ['contribution', 'contact'],
      'contribution_source' => NULL,
      'custom_field_mapping' => NULL,
      'membership_type_id' => NULL,
      'membership_type_id_recur' => NULL,
      'newsletter_double_opt_in' => NULL,
      'required_address_components' => [
        'street_address',
        'postal_code',
        'city',
        'country',
      ],
      'map_as_contribution_notes' => [],
      'map_as_contact_notes' => [],
    ]
    // Add contribution status for all payment methods.
    // phpcs:ignore Drupal.Formatting.SpaceUnaryOperator.PlusMinus
    + array_fill_keys(array_map(function($attribute) {
      return $attribute . '_status';
    }, array_keys(static::paymentInstruments())), CRM_Twingle_Submission::CONTRIBUTION_STATUS_COMPLETED));
  }

  /**
   * Retrieves the profile that matches the given project ID, i.e. the profile
   * which is responsible for processing the project's data.
   * Returns the default profile if no match was found.
   *
   * @param string $project_id
   *
   * @return CRM_Twingle_Profile
   * @throws \CRM\Twingle\Exceptions\ProfileException
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public static function getProfileForProject($project_id) {
    $profiles = self::getProfiles();
    $default_profile = NULL;

    foreach ($profiles as $profile) {
      if ($profile->matches($project_id)) {
        return $profile;
      }
      if ($profile->is_default()) {
        $default_profile = $profile;
      }
    }

    // If none matches, use the default profile.
    $default_profile = $profiles['default'];
    if (!empty($default_profile)) {
      return $default_profile;
    }
    else {
      throw new ProfileException(
        'Could not find default profile',
        ProfileException::ERROR_CODE_DEFAULT_PROFILE_NOT_FOUND
      );
    }
  }

  /**
   * Retrieves the profile with the given ID.
   *
   * @param int|NULL $id
   *
   * @return CRM_Twingle_Profile | NULL
   * @throws \Civi\Core\Exception\DBQueryException
   * @throws \Civi\Twingle\Exceptions\ProfileException
   */
  public static function getProfile(int $id = NULL) {
    if (isset($id)) {
      /**
       * @var CRM_Core_DAO $profile_data
       */
      $profile_data = CRM_Core_DAO::executeQuery(
        'SELECT id, name, config FROM civicrm_twingle_profile WHERE id = %1',
        [1 => [$id, 'Integer']]
      );
      if ($profile_data->fetch()) {
        return new CRM_Twingle_Profile(
          $profile_data->name,
          json_decode($profile_data->config, TRUE),
          (int) $profile_data->id
        );
      }
    }
    throw new ProfileException('Profile not found.', ProfileException::ERROR_CODE_PROFILE_NOT_FOUND);
  }

  /**
   * Retrieves the list of all profiles persisted within the current CiviCRM
   * settings, including the default profile.
   *
   * @return array<int, \CRM_Twingle_Profile>
   *   An array of profiles with profile IDs as keys and profile objects as values.
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public static function getProfiles(): array {
    // todo: cache?
    $profiles = [];
    /**
     * @var CRM_Core_DAO $profile_data
     */
    $profile_data = CRM_Core_DAO::executeQuery('SELECT id, name, config FROM civicrm_twingle_profile');
    while ($profile_data->fetch()) {
      $profiles[(int) $profile_data->id] = new CRM_Twingle_Profile(
        $profile_data->name,
        json_decode($profile_data->config, TRUE),
        (int) $profile_data->id
      );
    }
    return $profiles;
  }

  /**
   * Get the stats (access_count, last_access) for all twingle profiles
   *
   * @return array<string, array<string, mixed>>
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public static function getProfileStats() {
    $stats = [];
    /**
     * @var CRM_Core_DAO $profile_data
     */
    $profile_data = CRM_Core_DAO::executeQuery(
      'SELECT name, last_access, access_counter FROM civicrm_twingle_profile'
    );
    while ($profile_data->fetch()) {
      // phpcs:disable Drupal.Arrays.Array.ArrayIndentation
      $stats[(string) $profile_data->name] = [
        'name' => $profile_data->name,
        'last_access' => $profile_data->last_access,
        'last_access_txt' => $profile_data->last_access
          ? date('Y-m-d H:i:s', strtotime($profile_data->last_access))
          : E::ts('never'),
        'access_counter' => $profile_data->access_counter,
        'access_counter_txt' => $profile_data->access_counter
          ? ((int) $profile_data->access_counter) . 'x'
          : E::ts('never'),
      ];
      // phpcs:enable
    }
    return $stats;
  }

}
