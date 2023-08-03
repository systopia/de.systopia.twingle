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
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_Twingle_Form_Profile extends CRM_Core_Form {

  /**
   * @var CRM_Twingle_Profile $profile
   *
   * The profile object the form is acting on.
   */
  protected $profile;

  /**
   * @var string
   *
   * The operation to perform within the form.
   */
  protected $_op;

  /**
   * @var array
   *
   * A static cache of retrieved payment instruments found within
   * self::getPaymentInstruments().
   */
  protected static $_paymentInstruments = NULL;

  /**
   * @var array
   *
   * A static cache of retrieved contribution statuses found within
   * static::getContributionStatusOptions().
   */
  protected static $_contributionStatusOptions = NULL;

  /**
   * @var array
   *
   * A static cache of retrieved groups found within static::getGroups().
   */
  protected static $_groups = NULL;

  /**
   * @var array
   *
   * A static cache of retrieved newsletter groups found within
   * static::getNewsletterGroups().
   */
  protected static $_newsletterGroups = NULL;

  /**
   * @var array
   *
   * A static cache of retrieved campaigns found within static::getCampaigns().
   */
  protected static $_campaigns = NULL;

  /**
   * @var array
   *
   * A static cache of retrieved financial types found within
   * static::getFinancialTypes().
   */
  protected static $_financialTypes = NULL;

  /**
   * @var array
   *
   * A static cache of retrieved genders found within
   * static::getGenderOptions().
   */
  protected static $_genderOptions = NULL;

  /**
   * @var array
   *
   * A static cache of retrieved prefixes found within
   * static::getGenderOptions().
   */
  protected static $_prefixOptions = NULL;

  /**
   * @var array
   *
   * A static cache of retrieved location types found within
   * static::getLocationTypes().
   */
  protected static $_locationTypes = NULL;

  /**
   * @var array
   *
   * A static cache of retrieved location types found within
   * static::getXCMProfiles().
   */
  protected static $_xcm_profiles = NULL;

  /**
   * @var array
   *
   * A static cache of retrieved membership types found within
   * static::getMembershipTypes().
   */
  protected static $_membershipTypes = NULL;

  /**
   * @var array
   *
   * A static cache of retrieved CiviSEPA creditors found within
   * static::getSepaCreditors().
   */
  protected static $_sepaCreditors = NULL;

  /**
   * Builds the form structure.
   */
  public function buildQuickForm() {
    // "Create" is the default operation.
    if (!$this->_op = CRM_Utils_Request::retrieve('op', 'String', $this)) {
      $this->_op = 'create';
    }

    // Verify that a profile with the given name exists.
    $profile_name = CRM_Utils_Request::retrieve('name', 'String', $this);
    if (!$this->profile = CRM_Twingle_Profile::getProfile($profile_name)) {
      $profile_name = NULL;
    }

    // Set redirect destination.
    $this->controller->_destination = CRM_Utils_System::url('civicrm/admin/settings/twingle/profiles', 'reset=1');

    switch ($this->_op) {
      case 'delete':
        if ($profile_name) {
          CRM_Utils_System::setTitle(E::ts('Delete Twingle API profile <em>%1</em>', array(1 => $profile_name)));
          $this->addButtons(array(
            array(
              'type' => 'submit',
              'name' => ($profile_name == 'default' ? E::ts('Reset') : E::ts('Delete')),
              'isDefault' => TRUE,
            ),
          ));
        }
        parent::buildQuickForm();
        return;
      case 'edit':
        // When editing without a valid profile name, edit the default profile.
        if (!$profile_name) {
          $profile_name = 'default';
          $this->profile = CRM_Twingle_Profile::getProfile($profile_name);
        }
        CRM_Utils_System::setTitle(E::ts('Edit Twingle API profile <em>%1</em>', array(1 => $this->profile->getName())));
        break;
      case 'copy':
        // Retrieve the source profile name.
        $profile_name = CRM_Utils_Request::retrieve('source_name', 'String', $this);
        // When copying without a valid profile name, copy the default profile.
        if (!$profile_name) {
          $profile_name = 'default';
        }
        $this->profile = clone CRM_Twingle_Profile::getProfile($profile_name);

        // Propose a new name for this profile.
        $profile_name = $profile_name . '_copy';
        $this->profile->setName($profile_name);
        CRM_Utils_System::setTitle(E::ts('New Twingle API profile'));
        break;
      case 'create':
        // Load factory default profile values.
        $this->profile = CRM_Twingle_Profile::createDefaultProfile($profile_name);
        CRM_Utils_System::setTitle(E::ts('New Twingle API profile'));
        break;
    }

    // Assign template variables.
    $this->assign('op', $this->_op);
    $this->assign('profile_name', $profile_name);

    // Add form elements.
    $is_default = $profile_name == 'default';
    $this->add(
      ($is_default ? 'static' : 'text'),
      'name',
      E::ts('Profile name'),
      array(),
      !$is_default
    );

    $this->add(
      'text', // field type
      'selector', // field name
      E::ts('Project IDs'), // field label
      ['class' => 'huge'],
      TRUE // is required
    );

    $this->add(
        'select',
        'xcm_profile',
        E::ts('Contact Matcher (XCM) Profile'),
        static::getXCMProfiles(),
        TRUE
    );

    $this->add(
      'select',
      'location_type_id',
      E::ts('Location type'),
      static::getLocationTypes(),
      TRUE
    );

    $this->add(
      'select',
      'location_type_id_organisation',
      E::ts('Location type for organisations'),
      static::getLocationTypes(),
      TRUE
    );

    $this->add(
      'select', // field type
      'financial_type_id', // field name
      E::ts('Financial type'), // field label
      static::getFinancialTypes(), // list of options
      TRUE // is required
    );
    $this->add(
      'select', // field type
      'financial_type_id_recur', // field name
      E::ts('Financial type (recurring)'), // field label
      static::getFinancialTypes(), // list of options
      TRUE // is required
    );

    $this->add(
      'select',
      'gender_male',
      E::ts('Gender option for submitted value "male"'),
      static::getGenderOptions(),
      TRUE
    );
    $this->add(
      'select',
      'gender_female',
      E::ts('Gender option for submitted value "female"'),
      static::getGenderOptions(),
      TRUE
    );
    $this->add(
      'select',
      'gender_other',
      E::ts('Gender option for submitted value "other"'),
      static::getGenderOptions(),
      TRUE
    );

    $this->add(
        'select',
        'prefix_male',
        E::ts('Prefix option for submitted value "male"'),
        static::getPrefixOptions(),
        FALSE
    );
    $this->add(
        'select',
        'prefix_female',
        E::ts('Prefix option for submitted value "female"'),
        static::getPrefixOptions(),
        FALSE
    );
    $this->add(
        'select',
        'prefix_other',
        E::ts('Prefix option for submitted value "other"'),
        static::getPrefixOptions(),
        FALSE
    );

    $payment_instruments = CRM_Twingle_Profile::paymentInstruments();
    $this->assign('payment_instruments', $payment_instruments);
    foreach ($payment_instruments as $pi_name => $pi_label) {
      $this->add(
        'select', // field type
        $pi_name, // field name
        E::ts('Record %1 as', array(1 => $pi_label)), // field label
        static::getPaymentInstruments(), // list of options
        TRUE // is required
      );

      $this->add(
        'select',
        $pi_name . '_status',
        E::ts('Record %1 donations with contribution status', array(1 => $pi_label)),
        static::getContributionStatusOptions(),
        TRUE
      );
    }

    if (CRM_Twingle_Submission::civiSepaEnabled()) {
      $this->add(
        'select',
        'sepa_creditor_id',
        E::ts('CiviSEPA creditor'),
        static::getSepaCreditors(),
        TRUE
      );
    }

    $this->add(
      'checkbox', // field type
      'newsletter_double_opt_in', // field name
      E::ts('Use Double-Opt-In for newsletter'), // field label
      FALSE, // is not required
      array()
      );

    $this->add(
      'select', // field type
      'newsletter_groups', // field name
      E::ts('Sign up for newsletter groups'), // field label
      static::getNewsletterGroups(), // list of options
      FALSE, // is not required
      array('class' => 'crm-select2 huge', 'multiple' => 'multiple')
    );

    $this->add(
      'select', // field type
      'postinfo_groups', // field name
      E::ts('Sign up for postal mail groups'), // field label
      static::getPostinfoGroups(), // list of options
      FALSE, // is not required
      array('class' => 'crm-select2 huge', 'multiple' => 'multiple')
    );

    $this->add(
      'select', // field type
      'donation_receipt_groups', // field name
      E::ts('Sign up for Donation receipt groups'), // field label
      static::getDonationReceiptGroups(), // list of options
      FALSE, // is not required
      array('class' => 'crm-select2 huge', 'multiple' => 'multiple')
    );

    $this->add(
      'select', // field type
      'campaign', // field name
      E::ts('Default Campaign'), // field label
      array('' => E::ts('- none -')) + static::getCampaigns(), // list of options
      FALSE, // is not required
      array('class' => 'crm-select2 huge')
    );

    $this->add(
      'select',
      'campaign_targets',
      E::ts('Set Campaign for'),
      [
        'contribution' => E::ts("Contribution"),
        'recurring'    => E::ts("Recurring Contribution"),
        'membership'   => E::ts("Membership"),
        'mandate'      => E::ts("SEPA Mandate"),
        'contact'      => E::ts("Contacts (XCM)"),
      ],
      FALSE, // is not required
      ['class' => 'crm-select2 huge', 'multiple' => 'multiple']
    );

    $this->add(
      'select', // field type
      'membership_type_id', // field name
      E::ts('Create membership of type'), // field label
      array('' => E::ts('- none -')) + static::getMembershipTypes(), // list of options
      FALSE, // is not required
      array('class' => 'crm-select2 huge')
    );
    $this->add(
      'select', // field type
      'membership_type_id_recur', // field name
      E::ts('Create membership of type (recurring)'), // field label
      array('' => E::ts('- none -')) + static::getMembershipTypes(), // list of options
      FALSE, // is not required
      array('class' => 'crm-select2 huge')
    );
    $this->add(
        'text',
        'membership_postprocess_call',
        E::ts('API Call for Membership Postprocessing'),
        FALSE
    );
    $this->addRule('membership_postprocess_call', E::ts("The API call must have the form 'Entity.Action'."), 'regex', '/^[A-Za-z_]+[.][A-Za-z_]+$/');

    $this->add(
      'text', // field type
      'contribution_source', // field name
      E::ts('Contribution source'), // field label
      array()
    );

    $this->add(
      'select',
      'required_address_components',
      E::ts('Required address components'),
      [
        'street_address' => E::ts("Street"),
        'postal_code'    => E::ts("Postal Code"),
        'city'           => E::ts("City"),
        'country'        => E::ts("Country"),
      ],
      FALSE, // is not required
      ['class' => 'crm-select2 huge', 'multiple' => 'multiple']
    );

    $this->add(
      'textarea', // field type
      'custom_field_mapping', // field name
      E::ts('Custom field mapping'), // field label
      array()
    );

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => E::ts('Save'),
        'isDefault' => TRUE,
      ),
    ));

    // Export form elements.
    parent::buildQuickForm();
  }

  /**
   * Validates the profile form.
   *
   * @param array $values
   *   The submitted form values, keyed by form element name.
   *
   * @return bool | array
   *   TRUE when the form was successfully validated, or an array of error
   *   messages, keyed by form element name.
   */
  public function validate() {
    $values = $this->exportValues();

    // Validate new profile names.
    if (
      isset($values['name'])
      && ($values['name'] != $this->profile->getName() || $this->_op != 'edit')
      && !empty(CRM_Twingle_Profile::getProfile($values['name']))
    ) {
      $this->_errors['name'] = E::ts('A profile with this name already exists.');
    }

    // Restrict profile names to alphanumeric characters and the underscore.
    if (isset($values['name']) && preg_match("/[^A-Za-z0-9\_]/", $values['name'])) {
      $this->_errors['name'] = E::ts('Only alphanumeric characters and the underscore (_) are allowed for profile names.');
    }

    // Validate custom field mapping.
    try {
      if (isset($values['custom_field_mapping'])) {
        $custom_field_mapping = preg_split('/\r\n|\r|\n/', $values['custom_field_mapping'], -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($custom_field_mapping)) {
          throw new Exception(
            E::ts('Could not parse custom field mapping.')
          );
        }
        foreach ($custom_field_mapping as $custom_field_map) {
          $custom_field_map = explode("=", $custom_field_map);
          if (count($custom_field_map) !== 2) {
            throw new Exception(
              E::ts('Could not parse custom field mapping.')
            );
          }
          list($twingle_field_name, $custom_field_name) = $custom_field_map;
          $custom_field_id = substr($custom_field_name, strlen('custom_'));

          // Check for custom field existence
          try {
            $custom_field = civicrm_api3('CustomField', 'getsingle', array(
              'id' => $custom_field_id,
            ));
          }
          catch (CiviCRM_API3_Exception $exception) {
            throw new Exception(
              E::ts(
                'Custom field custom_%1 does not exist.',
                array(1 => $custom_field_id)
              )
            );
          }

          // Only allow custom fields on relevant entities.
          try {
            $custom_group = civicrm_api3('CustomGroup', 'getsingle', array(
              'id' => $custom_field['custom_group_id'],
              'extends' => array(
                'IN' => array(
                  'Contact',
                  'Individual',
                  'Organization',
                  'Contribution',
                  'ContributionRecur',
                ),
              ),
            ));
          } catch (CiviCRM_API3_Exception $exception) {
            throw new Exception(
              E::ts(
                'Custom field custom_%1 is not in a CustomGroup that extends one of the supported CiviCRM entities.',
                array(1 => $custom_field['id'])
              )
            );
          }
        }
      }
    }
    catch (Exception $exception) {
      $this->_errors['custom_field_mapping'] = $exception->getMessage();
    }

    return parent::validate();
  }

  /**
   * Set the default values (i.e. the profile's current data) in the form.
   */
  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();
    if (in_array($this->_op, array('create', 'edit', 'copy'))) {
      $defaults['name'] = $this->profile->getName();
      $profile_data = $this->profile->getData();
      foreach ($profile_data as $element_name => $value) {
        $defaults[$element_name] = $value;
      }
      // backwards compatibility, see issue #27
      if (!isset($profile_data['campaign_targets'])) {
        $defaults['campaign_targets'] = ['contribution', 'contact'];
      }
    }
    return $defaults;
  }

  /**
   * Store the values submitted with the form in the profile.
   */
  public function postProcess() {
    $values = $this->exportValues();
    try {
      if (in_array($this->_op, ['create', 'edit', 'copy'])) {
        if (empty($values['name'])) {
          $values['name'] = 'default';
        }
        $this->profile->setName($values['name']);
        foreach ($this->profile->getData() as $element_name => $value) {
          if ($element_name == 'newsletter_double_opt_in') {
            $values[$element_name] = (int) isset($values[$element_name]);
          }
          if (isset($values[$element_name])) {
            $this->profile->setAttribute($element_name, $values[$element_name]);
          }
        }
        $this->profile->saveProfile();
      }
      elseif ($this->_op == 'delete') {
        $this->profile->deleteProfile();
      }
    } catch (ProfileException $e) {
      Civi::log()->error($e->getLogMessage());
      CRM_Core_Session::setStatus(
        E::ts('Error'),
        $e->getMessage(),
        'error',
        ['unique' => TRUE]
      );
    }
    parent::postProcess();
  }

  /**
   * Retrieves location types present within the system as options for select
   * form elements.
   *
   * @return array
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function getLocationTypes() {
    if (!isset(static::$_locationTypes)) {
      static::$_locationTypes = array();
      $query = civicrm_api3('LocationType', 'get', array(
        'option.limit' => 0,
        'is_active' => 1,
      ));
      foreach ($query['values'] as $type) {
        static::$_locationTypes[$type['id']] = $type['name'];
      }
    }
    return static::$_locationTypes;
  }

  /**
   * Retrieves XCM profiles (if supported). 'default' profile is always available
   *
   * @return array
   */
  public static function getXCMProfiles() {
    if (!isset(static::$_xcm_profiles)) {
      static::$_xcm_profiles = array(
          '' => E::ts("&lt;default profile&gt;"),
      );
      if (method_exists('CRM_Xcm_Configuration', 'getProfileList')) {
        $profiles = CRM_Xcm_Configuration::getProfileList();
        foreach ($profiles as $profile_key => $profile_name) {
          static::$_xcm_profiles[$profile_key] = $profile_name;
        }
      }
    }
    return static::$_xcm_profiles;
  }

  /**
   * Retrieves financial types present within the system as options for select
   * form elements.
   *
   * @return array
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function getFinancialTypes() {
    if (!isset(static::$_financialTypes)) {
      static::$_financialTypes = array();
      $query = civicrm_api3('FinancialType', 'get', array(
        'option.limit' => 0,
        'is_active' => 1,
        'return' => 'id,name'
      ));
      foreach ($query['values'] as $type) {
        static::$_financialTypes[$type['id']] = $type['name'];
      }
    }
    return static::$_financialTypes;
  }

  /**
   * Retrieves membership types present within the system as options for select
   * form elements.
   *
   * @return array
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function getMembershipTypes() {
    if (!isset(static::$_membershipTypes)) {
      static::$_membershipTypes = array();
      $query = civicrm_api3('MembershipType', 'get', array(
        'option.limit' => 0,
        'is_active' => 1,
        'return' => 'id,name'
      ));
      foreach ($query['values'] as $type) {
        static::$_membershipTypes[$type['id']] = $type['name'];
      }
    }
    return static::$_membershipTypes;
  }

  /**
   * Retrieves genders present within the system as options for select form
   * elements.
   *
   * @return array
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function getGenderOptions() {
    if (!isset(static::$_genderOptions)) {
      static::$_genderOptions = array();
      $query = civicrm_api3('OptionValue', 'get', array(
        'option.limit' => 0,
        'option_group_id' => 'gender',
        'is_active' => 1,
        'return' => array(
          'value',
          'label',
        ),
      ));
      foreach ($query['values'] as $gender) {
        static::$_genderOptions[$gender['value']] = $gender['label'];
      }
    }
    return static::$_genderOptions;
  }

  /**
   * Retrieves prefixes present within the system as options for select form
   * elements.
   *
   * @return array
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function getPrefixOptions() {
    if (!isset(static::$_prefixOptions)) {
      static::$_prefixOptions = array('' => E::ts('none'));
      $query = civicrm_api3('OptionValue', 'get', array(
          'option.limit' => 0,
          'option_group_id' => 'individual_prefix',
          'is_active' => 1,
          'return' => array(
              'value',
              'label',
          ),
      ));
      foreach ($query['values'] as $prefix) {
        static::$_prefixOptions[$prefix['value']] = $prefix['label'];
      }
    }
    return static::$_prefixOptions;
  }

  /**
   * Retrieves CiviSEPA creditors as options for select form elements.
   *
   * @return array
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function getSepaCreditors() {
    if (!isset(static::$_sepaCreditors)) {
      static::$_sepaCreditors = array();
      if (CRM_Twingle_Submission::civiSepaEnabled()) {
        $result = civicrm_api3('SepaCreditor', 'get', array(
          'option.limit' => 0,
        ));
        foreach ($result['values'] as $sepa_creditor) {
          static::$_sepaCreditors[$sepa_creditor['id']] = $sepa_creditor['name'];
        }
      }
    }
    return static::$_sepaCreditors;
  }

  /**
   * Retrieves payment instruments present within the system as options for
   * select form elements.
   *
   * @return array
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function getPaymentInstruments() {
    if (!isset(self::$_paymentInstruments)) {
      self::$_paymentInstruments = array();
      $query = civicrm_api3('OptionValue', 'get', array(
        'option.limit' => 0,
        'option_group_id' => 'payment_instrument',
        'is_active'  => 1,
        'return' => 'value,label'
      ));
      foreach ($query['values'] as $payment_instrument) {
        // Do not include CiviSEPA payment instruments, but add a SEPA option if
        // enabled.
        if (
          CRM_Twingle_Submission::civiSepaEnabled()
              && CRM_Twingle_Tools::isSDD($payment_instrument['value'])
        ) {
          if (!isset(self::$_paymentInstruments['sepa'])) {
            self::$_paymentInstruments['sepa'] = E::ts('CiviSEPA');
          }
        }
        else {
          self::$_paymentInstruments[$payment_instrument['value']] = $payment_instrument['label'];
        }
      }
    }
    return self::$_paymentInstruments;
  }

  /**
   * Retrieves contribution statuses as options for select form elements.
   *
   * @return array
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function getContributionStatusOptions() {
    if (!isset(self::$_contributionStatusOptions)) {
      $query = civicrm_api3(
        'OptionValue',
        'get',
        array(
          'option.limit' => 0,
          'option_group_id' => 'contribution_status',
          'return' => array(
            'value',
            'label',
          )
        )
      );

      foreach ($query['values'] as $contribution_status) {
        self::$_contributionStatusOptions[$contribution_status['value']] = $contribution_status['label'];
      }
    }

    return self::$_contributionStatusOptions;
  }

  /**
   * Retrieves active groups used as mailing lists within the system as options
   * for select form elements.
   *
   * @return array
   *
   * @throws \CiviCRM_API3_Exception
   *
   */
  public static function getNewsletterGroups() {
    if (!isset(static::$_newsletterGroups)) {
      static::$_newsletterGroups = array();
      $group_types = civicrm_api3('OptionValue', 'get', array(
        'option.limit' => 0,
        'option_group_id' => 'group_type',
        'name' => CRM_Twingle_Submission::GROUP_TYPE_NEWSLETTER,
      ));
      if ($group_types['count'] > 0) {
        $group_type = reset($group_types['values']);
        $query = civicrm_api3('Group', 'get', array(
          'is_active' => 1,
          'group_type' => array('LIKE' => '%' . CRM_Utils_Array::implodePadded($group_type['value']) . '%'),
          'option.limit'   => 0,
          'return'         => 'id,name'
        ));
        foreach ($query['values'] as $group) {
          static::$_newsletterGroups[$group['id']] = $group['name'];
        }
      }
      else {
        static::$_newsletterGroups[''] = E::ts('No mailing lists available');
      }
    }
    return static::$_newsletterGroups;
  }

  /**
   * Retrieves active groups as options for select form elements.
   *
   * @return array
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function getGroups() {
    if (!isset(static::$_groups)) {
      static::$_groups = array();
      $query = civicrm_api3('Group', 'get', array(
        'option.limit' => 0,
        'is_active' => 1,
        'return' => 'id,name'
      ));
      foreach ($query['values'] as $group) {
        static::$_groups[$group['id']] = $group['name'];
      }
    }
    return static::$_groups;
  }

  /**
   * Retrieves active groups used as postal mailing lists within the system as
   * options for select form elements.
   *
   * @return array
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function getPostinfoGroups() {
    return static::getGroups();
  }

  /**
   * Retrieves active groups used as donation receipt requester lists within the
   * system as options for select form elements.
   *
   * @return array
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function getDonationReceiptGroups() {
    return static::getGroups();
  }

  /**
   * Retrieves campaigns as options for select elements.
   *
   * @return array
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function getCampaigns() {
    if (!isset(static::$_campaigns)) {
      static::$_campaigns = array();
      $query = civicrm_api3('Campaign', 'get', array(
        'option.limit' => 0,
        'return' => array(
          'id',
          'title',
        )
      ));
      foreach ($query['values'] as $campaign) {
        static::$_campaigns[$campaign['id']] = $campaign['title'];
      }
    }
    return static::$_campaigns;
  }

}
