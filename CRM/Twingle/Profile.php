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
 * Profiles define how incoming submissions from the Twingle API are
 * processed in CiviCRM.
 */
class CRM_Twingle_Profile {

  /**
   * @var CRM_Twingle_Profile[] $_profiles
   *   Caches the profile objects.
   */
  protected static $_profiles = NULL;

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
   * Checks whether the profile's selector matches the given project ID.
   *
   * @param string | int $project_id
   *
   * @return bool
   */
  public function matches($project_id) {
    $selector = $this->getAttribute('selector');
    $project_ids = explode(',', $selector);
    return in_array($project_id, $project_ids);
  }

  /**
   * @return array
   *   The profile's configured custom field mapping
   */
  public function getCustomFieldMapping() {
    $custom_field_mapping = array();
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
   * @throws \Exception
   *   When the attribute name is not known.
   */
  public function setAttribute($attribute_name, $value) {
    if (!in_array($attribute_name, self::allowedAttributes())) {
      throw new Exception(E::ts('Unknown attribute %1.', array(1 => $attribute_name)));
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
    self::$_profiles[$this->getName()] = $this;
    $this->verifyProfile();
    self::storeProfiles();
  }

  /**
   * Deletes the profile from the CiviCRM settings.
   */
  public function deleteProfile() {
    unset(self::$_profiles[$this->getName()]);
    self::storeProfiles();
  }

  /**
   * Returns an array of attributes allowed for a profile.
   *
   * @return array
   */
  public static function allowedAttributes() {
    return array_merge(
      array(
        'selector',
        'location_type_id',
        'location_type_id_organisation',
        'financial_type_id',
        'financial_type_id_recur',
        'sepa_creditor_id',
        'gender_male',
        'gender_female',
        'gender_other',
        'newsletter_groups',
        'postinfo_groups',
        'donation_receipt_groups',
        'campaign',
        'contribution_source',
        'custom_field_mapping',
        'membership_type_id',
      ),
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
    return array(
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
    );
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
    return new CRM_Twingle_Profile($name, array(
      'selector'          => '',
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
      'sepa_creditor_id' => NULL,
      'gender_male' => 2,
      'gender_female' => 1,
      'gender_other' => 3,
      'newsletter_groups' => NULL,
      'postinfo_groups' => NULL,
      'donation_receipt_groups' => NULL,
      'campaign' => NULL,
      'contribution_source' => NULL,
      'custom_field_mapping' => NULL,
      'membership_type_id' => NULL,
    )
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
   * @param $name
   *
   * @return CRM_Twingle_Profile | NULL
   */
  public static function getProfile($name) {
    $profiles = self::getProfiles();
    if (isset($profiles[$name])) {
      return $profiles[$name];
    }
    else {
      return NULL;
    }
  }

  /**
   * Retrieves the list of all profiles persisted within the current CiviCRM
   * settings, including the default profile.
   *
   * @return CRM_Twingle_Profile[]
   */
  public static function getProfiles() {
    if (self::$_profiles === NULL) {
      self::$_profiles = array();
      if ($profiles_data = Civi::settings()->get('twingle_profiles')) {
        foreach ($profiles_data as $profile_name => $profile_data) {
          self::$_profiles[$profile_name] = new CRM_Twingle_Profile($profile_name, $profile_data);
        }
      }
    }

    // Include the default profile if it was not overridden within the settings.
    if (!isset(self::$_profiles['default'])) {
      self::$_profiles['default'] = self::createDefaultProfile();
      self::storeProfiles();
    }

    return self::$_profiles;
  }


  /**
   * Persists the list of profiles into the CiviCRM settings.
   */
  public static function storeProfiles() {
    $profile_data = array();
    foreach (self::$_profiles as $profile_name => $profile) {
      $profile_data[$profile_name] = $profile->data;
    }
    Civi::settings()->set('twingle_profiles', $profile_data);
  }
}
