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
use CRM_Twingle_Exceptions_ProfileException as ProfileException;
use CRM_Twingle_Exceptions_ProfileValidationError as ProfileValidationError;

/**
 * Profiles define how incoming submissions from the Twingle API are
 * processed in CiviCRM.
 */
class CRM_Twingle_Profile {

  /**
   * @var string $name
   *   The name of the profile.
   */
  protected $name = NULL;

  /**
   * @var array $data
   *   The properties of the profile.
   */
  protected $data = NULL;

  /**
   * CRM_Twingle_Profile constructor.
   *
   * @param string $name
   *   The name of the profile.
   * @param array $data
   *   The properties of the profile
   */
  public function __construct($name, $data) {
    $this->name = $name;
    $allowed_attributes = self::allowedAttributes();
    $this->data = $data + array_combine(
        $allowed_attributes,
        array_fill(0, count($allowed_attributes), NULL)
      );
  }

  /**
   * Logs (production) access to this profile
   *
   * @return bool
   */
  public function logAccess() {
    CRM_Core_DAO::executeQuery("
        UPDATE civicrm_twingle_profile 
        SET 
            last_access = NOW(), 
            access_counter = access_counter + 1
        WHERE name = %1", [1 => [$this->name, 'String']]);
  }

  /**
   * Checks whether the profile's selector matches the given project ID.
   *
   * @param string | int $project_id
   *
   * @return bool
   */
  public function matches($project_id) {
    $selector = $this->getAttribute('selector');
    $project_ids = array_map(
      function($project_id) {
        return trim($project_id);
      },
      explode(',', $selector)
    );
    return in_array($project_id, $project_ids);
  }

  /**
   * @return array
   *   The profile's configured custom field mapping
   */
  public function getCustomFieldMapping() {
    $custom_field_mapping = [];
    if (!empty($custom_field_definition = $this->getAttribute('custom_field_mapping'))) {
      foreach (preg_split('/\r\n|\r|\n/', $custom_field_definition, -1, PREG_SPLIT_NO_EMPTY) as $custom_field_map) {
        list($twingle_field_name, $custom_field_name) = explode("=", $custom_field_map);
        $custom_field_mapping[$twingle_field_name] = $custom_field_name;
      }
    }
    return $custom_field_mapping;
  }

  /**
   * Retrieves all data attributes of the profile.
   *
   * @return array
   */
  public function getData() {
    return $this->data;
  }

  /**
   * Retrieves the profile name.
   *
   * @return string
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Sets the profile name.
   *
   * @param $name
   */
  public function setName($name) {
    $this->name = $name;
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
    if (isset($this->data[$attribute_name])) {
      return $this->data[$attribute_name];
    }
    else {
      return $default;
    }
  }

  /**
   * Sets an attribute of the profile.
   *
   * @param string $attribute_name
   * @param mixed $value
   *
   * @throws \CRM_Twingle_Exceptions_ProfileException
   *   When the attribute name is not known.
   */
  public function setAttribute($attribute_name, $value) {
    if (!in_array($attribute_name, self::allowedAttributes())) {
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
   * @param $twingle_id string Twingle ID
   * @return string CiviCRM transaction ID
   */
  public function getTransactionID($twingle_id) {
    $prefix = Civi::settings()->get('twingle_prefix');
    if (empty($prefix)) {
      return $twingle_id;
    } else {
      return $prefix . $twingle_id;
    }
  }

  /**
   * Verifies whether the profile is valid (i.e. consistent and not colliding
   * with other profiles).
   *
   * @throws Exception
   *   When the profile could not be successfully validated.
   */
  public function verifyProfile() {
    // TODO: check
    //  data of this profile consistent?
    //  conflicts with other profiles?
  }

  /**
   * Persists the profile within the CiviCRM settings.
   */
  public function saveProfile() {
    // make sure it's valid
    $this->verifyProfile();

    // check if the profile exists
    $profile_id = CRM_Core_DAO::singleValueQuery(
      "SELECT id FROM civicrm_twingle_profile WHERE name = %1", [1 => [$this->name, 'String']]);
    if ($profile_id) {
      // existing profile -> just update the config
      CRM_Core_DAO::executeQuery(
        "UPDATE civicrm_twingle_profile SET config = %2 WHERE name = %1",
        [
          1 => [$this->name, 'String'],
          2 => [json_encode($this->data), 'String']
        ]);
    } else {
      // new profile -> add new entry to the DB
      CRM_Core_DAO::executeQuery(
        "INSERT IGNORE INTO civicrm_twingle_profile(name,config,last_access,access_counter) VALUES (%1, %2, null, 0)",
        [
          1 => [$this->name, 'String'],
          2 => [json_encode($this->data), 'String']
        ]);
    }
  }

  /**
   * Deletes the profile from the database
   */
  public function deleteProfile() {
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_twingle_profile WHERE name = %1", [1 => [$this->name, 'String']]);
  }

  /**
   * Returns an array of attributes allowed for a profile.
   *
   * @return array
   */
  public static function allowedAttributes() {
    return array_merge(
      [
        'selector',
        'xcm_profile',
        'location_type_id',
        'location_type_id_organisation',
        'financial_type_id',
        'financial_type_id_recur',
        'sepa_creditor_id',
        'gender_male',
        'gender_female',
        'gender_other',
        'prefix_male',
        'prefix_female',
        'prefix_other',
        'newsletter_groups',
        'postinfo_groups',
        'donation_receipt_groups',
        'campaign',
        'campaign_targets',
        'contribution_source',
        'custom_field_mapping',
        'membership_type_id',
        'membership_type_id_recur',
        'membership_postprocess_call',
        'newsletter_double_opt_in',
        'required_address_components',
      ],
      // Add payment methods.
      array_keys(static::paymentInstruments()),

      // Add contribution status for all payment methods.
      array_map(function ($attribute) {
        return $attribute . '_status';
      }, array_keys(static::paymentInstruments()))
    );
  }

  /**
   * Retrieves a list of supported payment methods.
   *
   * @return array
   */
  public static function paymentInstruments() {
    return [
      'pi_banktransfer' => E::ts('Bank transfer'),
      'pi_debit_manual' => E::ts('Debit manual'),
      'pi_debit_automatic' => E::ts('Debit automatic'),
      'pi_creditcard' => E::ts('Credit card'),
      'pi_mobilephone_germany' => E::ts('Mobile phone Germany'),
      'pi_paypal' => E::ts('PayPal'),
      'pi_sofortueberweisung' => E::ts('SOFORT Überweisung'),
      'pi_amazonpay' => E::ts('Amazon Pay'),
      'pi_paydirekt' => E::ts('paydirekt'),
      'pi_applepay' => E::ts('Apple Pay'),
      'pi_googlepay' =>  E::ts('Google Pay'),
      'pi_paydirekt' => E::ts('Paydirekt'),
      'pi_twint' => E::ts('Twint'),
      'pi_ideal' => E::ts('iDEAL'),
      'pi_post_finance' => E::ts('Postfinance'),
      'pi_bancontact' => E::ts('Bancontact'),
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
      'selector'          => '',
      'xcm_profile'       => '',
      'location_type_id'  => CRM_Twingle_Submission::LOCATION_TYPE_ID_WORK,
      'location_type_id_organisation' => CRM_Twingle_Submission::LOCATION_TYPE_ID_WORK,
      'financial_type_id' => 1, // "Donation"
      'financial_type_id_recur' => 1, // "Donation"
      'pi_banktransfer' => 5, // "EFT"
      'pi_debit_manual' => NULL,
      'pi_debit_automatic' => 3, // Debit
      'pi_creditcard' => 1, // "Credit Card"
      'pi_mobilephone_germany' => NULL,
      'pi_paypal' => NULL,
      'pi_sofortueberweisung' => NULL,
      'pi_amazonpay' => NULL,
      'pi_paydirekt' => NULL,
      'pi_applepay' => NULL,
      'pi_googlepay' => NULL,
      'pi_paydirekt' => NULL,
      'pi_twint' => NULL,
      'pi_ideal' => NULL,
      'pi_post_finance' => NULL,
      'pi_bancontact' => NULL,
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
    ]
      // Add contribution status for all payment methods.
      + array_fill_keys(array_map(function($attribute) {
        return $attribute . '_status';
      }, array_keys(static::paymentInstruments())), CRM_Twingle_Submission::CONTRIBUTION_STATUS_COMPLETED));
  }

  /**
   * Retrieves the profile that matches the given project ID, i.e. the profile
   * which is responsible for processing the project's data.
   * Returns the default profile if no match was found.
   *
   * @param $project_id
   *
   * @return CRM_Twingle_Profile
   */
  public static function getProfileForProject($project_id) {
    $profiles = self::getProfiles();

    foreach ($profiles as $profile) {
      if ($profile->matches($project_id)) {
        return $profile;
      }
    }

    // If none matches, use the default profile.
    return $profiles['default'];
  }

  /**
   * Retrieves the profile with the given name.
   *
   * @param string $name
   *
   * @return CRM_Twingle_Profile | NULL
   */
  public static function getProfile($name) {
    if (!empty($name)) {
      $profile_data = CRM_Core_DAO::singleValueQuery("SELECT config FROM civicrm_twingle_profile WHERE name = %1", [
        1 => [$name, 'String']]);
      if ($profile_data) {
        return new CRM_Twingle_Profile($name, json_decode($profile_data, 1));
      }
    }
    return NULL;
  }

  /**
   * Retrieves the list of all profiles persisted within the current CiviCRM
   * settings, including the default profile.
   *
   * @return array
   *   profile_name => CRM_Twingle_Profile
   */
  public static function getProfiles() {
    // todo: cache?
    $profiles = [];
    $profile_data = CRM_Core_DAO::executeQuery("SELECT name, config FROM civicrm_twingle_profile");
    while ($profile_data->fetch()) {
      $profiles[$profile_data->name] = new CRM_Twingle_Profile($profile_data->name, json_decode($profile_data->config, 1));
    }
    return $profiles;
  }

  /**
   * Get the stats (access_count, last_access) for all twingle profiles
   *
   * @return CRM_Twingle_Profile[]
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public static function getProfileStats() {
    $stats = [];
    $profile_data = CRM_Core_DAO::executeQuery("SELECT name, last_access, access_counter FROM civicrm_twingle_profile");
    while ($profile_data->fetch()) {
      $stats[$profile_data->name] = [
        'name' => $profile_data->name,
        'last_access' => $profile_data->last_access,
        'last_access_txt' => $profile_data->last_access ? date('Y-m-d H:i:s', strtotime($profile_data->last_access)) : E::ts("never"),
        'access_counter' => $profile_data->access_counter,
        'access_counter_txt' => $profile_data->access_counter ? ((int) $profile_data->access_counter) . 'x' : E::ts("never"),
      ];
    }
    return $stats;
  }

}
