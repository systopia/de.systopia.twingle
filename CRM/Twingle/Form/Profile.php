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
   * Builds the form structure.
   */
  public function buildQuickForm() {
    // "Create" is the default operation.
    if (!$this->_op = CRM_Utils_Request::retrieve('op', 'String', $this)) {
      $this->_op = 'create';
    }

    // Verify that profile with the given name exists.
    $profile_name = CRM_Utils_Request::retrieve('name', 'String', $this);
    if (!$this->profile = CRM_Twingle_Profile::getProfile($profile_name)) {
      $profile_name = NULL;
    }

    // Assign template variables.
    $this->assign('op', $this->_op);
    $this->assign('profile_name', $profile_name);

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
      case 'create':
        // Load factory default profile values.
        $this->profile = CRM_twingle_Profile::createDefaultProfile($profile_name);
        CRM_Utils_System::setTitle(E::ts('New Twingle API profile'));
        break;
    }

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
      array(),
      TRUE // is required
    );

    $this->add(
      'select',
      'location_type_id',
      E::ts('Location type'),
      $this->getLocationTypes(),
      TRUE
    );

    $this->add(
      'select', // field type
      'financial_type_id', // field name
      E::ts('Financial type'), // field label
      $this->getFinancialTypes(), // list of options
      TRUE // is required
    );

    $this->add(
      'select',
      'gender_male',
      E::ts('Gender option for submitted value "male"'),
      $this->getGenderOptions(),
      TRUE
    );
    $this->add(
      'select',
      'gender_female',
      E::ts('Gender option for submitted value "female"'),
      $this->getGenderOptions(),
      TRUE
    );
    $this->add(
      'select',
      'gender_other',
      E::ts('Gender option for submitted value "other"'),
      $this->getGenderOptions(),
      TRUE
    );

    $payment_instruments = CRM_Twingle_Profile::paymentInstruments();
    $this->assign('payment_instruments', $payment_instruments);
    foreach ($payment_instruments as $pi_name => $pi_label) {
      $this->add(
        'select', // field type
        $pi_name, // field name
        E::ts('Record %1 as', array(1 => $pi_label)), // field label
        $this->getPaymentInstruments(), // list of options
        TRUE // is required
      );
    }

    if (CRM_Twingle_Submission::civiSepaEnabled()) {
      $this->add(
        'select',
        'sepa_creditor_id',
        E::ts('CiviSEPA creditor'),
        $this->getSepaCreditors(),
        TRUE
      );
    }

    $this->add(
      'select', // field type
      'newsletter_groups', // field name
      E::ts('Sign up for newsletter groups'), // field label
      $this->getNewsletterGroups(), // list of options
      FALSE, // is not required
      array('class' => 'crm-select2 huge', 'multiple' => 'multiple')
    );

    $this->add(
      'select', // field type
      'postinfo_groups', // field name
      E::ts('Sign up for postal mail groups'), // field label
      $this->getPostinfoGroups(), // list of options
      FALSE, // is not required
      array('class' => 'crm-select2 huge', 'multiple' => 'multiple')
    );

    $this->add(
      'select', // field type
      'donation_receipt_groups', // field name
      E::ts('Sign up for Donation receipt groups'), // field label
      $this->getDonationReceiptGroups(), // list of options
      FALSE, // is not required
      array('class' => 'crm-select2 huge', 'multiple' => 'multiple')
    );

    $this->add(
      'select', // field type
      'campaign', // field name
      E::ts('Assign donation to campaign'), // field label
      $this->getCampaigns(), // list of options
      FALSE, // is not required
      array('class' => 'crm-select2 huge')
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
   * @inheritdoc
   */
  public function addRules() {
    $this->addFormRule(array('CRM_Twingle_Form_Profile', 'validateProfileForm'));
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
  public static function validateProfileForm($values) {
    $errors = array();

    // Restrict profile names to alphanumeric characters and the underscore.
    if (isset($values['name']) && preg_match("/[^A-Za-z0-9\_]/", $values['name'])) {
      $errors['name'] = E::ts('Only alphanumeric characters and the underscore (_) are allowed for profile names.');
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Set the default values (i.e. the profile's current data) in the form.
   */
  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();
    if (in_array($this->_op, array('create', 'edit'))) {
      $defaults['name'] = $this->profile->getName();
      foreach ($this->profile->getData() as $element_name => $value) {
        $defaults[$element_name] = $value;
      }
    }
    return $defaults;
  }

  /**
   * Store the values submitted with the form in the profile.
   */
  public function postProcess() {
    $values = $this->exportValues();
    if (in_array($this->_op, array('create', 'edit'))) {
      if (empty($values['name'])) {
        $values['name'] = 'default';
      }
      $this->profile->setName($values['name']);
      foreach ($this->profile->getData() as $element_name => $value) {
        if (isset($values[$element_name])) {
          $this->profile->setAttribute($element_name, $values[$element_name]);
        }
      }
      $this->profile->saveProfile();
    }
    elseif ($this->_op == 'delete') {
      $this->profile->deleteProfile();
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
  public function getLocationTypes() {
    $location_types = array();
    $query = civicrm_api3('LocationType', 'get', array(
      'is_active' => 1,
    ));
    foreach ($query['values'] as $type) {
      $location_types[$type['id']] = $type['name'];
    }

    return $location_types;
  }

  /**
   * Retrieves financial types present within the system as options for select
   * form elements.
   *
   * @return array
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function getFinancialTypes() {
    $financial_types = array();
    $query = civicrm_api3('FinancialType', 'get', array(
      'is_active'    => 1,
      'option.limit' => 0,
      'return'       => 'id,name'
    ));
    foreach ($query['values'] as $type) {
      $financial_types[$type['id']] = $type['name'];
    }
    return $financial_types;
  }

  /**
   * Retrieves campaigns present within the system as options for select form
   * elements.
   *
   * @return array
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function getGenderOptions() {
    $genders = array();
    $query = civicrm_api3('OptionValue', 'get', array(
      'option_group_id' => 'gender',
      'is_active'    => 1,
      'option.limit' => 0,
      'return' => array(
        'value',
        'label',
      ),
    ));
    foreach ($query['values'] as $gender) {
      $genders[$gender['value']] = $gender['label'];
    }
    return $genders;
  }

  /**
   * Retrieves CiviSEPA creditors as options for select form elements.
   *
   * @return array
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function getSepaCreditors() {
    $creditors = array();

    if (CRM_Twingle_Submission::civiSepaEnabled()) {
      $result = civicrm_api3('SepaCreditor', 'get', array(
        'option.limit' => 0,
      ));
      foreach ($result['values'] as $sepa_creditor) {
        $creditors[$sepa_creditor['id']] = $sepa_creditor['name'];
      }
    }

    return $creditors;
  }

  /**
   * Retrieves payment instruments present within the system as options for
   * select form elements.
   */
  public function getPaymentInstruments() {
    if (!isset(self::$_paymentInstruments)) {
      self::$_paymentInstruments = array();
      $query = civicrm_api3('OptionValue', 'get', array(
        'option_group_id' => 'payment_instrument',
        'is_active'  => 1,
        'option.limit'    => 0,
        'return'          => 'value,label'
      ));
      foreach ($query['values'] as $payment_instrument) {
        // Do not include CiviSEPA payment instruments, but add a SEPA option if
        // enabled.
        if (
          CRM_Twingle_Submission::civiSepaEnabled()
          && (
            (
              method_exists('CRM_Sepa_Logic_PaymentInstruments', 'isSDD')
              && CRM_Sepa_Logic_PaymentInstruments::isSDD(array(
                'payment_instrument_id' => $payment_instrument['value'],
              ))
            )
            || (
              method_exists('CRM_Sepa_Logic_Settings', 'isSDD')
              && CRM_Sepa_Logic_Settings::isSDD(array(
                'payment_instrument_id' => $payment_instrument['value'],
              ))
            )
          )
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
   * Retrieves active groups used as mailing lists within the system as options
   * for select form elements.
   */
  public function getNewsletterGroups() {
    $groups = array();
    $group_types = civicrm_api3('OptionValue', 'get', array(
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
        $groups[$group['id']] = $group['name'];
      }
    }
    else {
      $groups[''] = E::ts('No mailing lists available');
    }
    return $groups;
  }

  /**
   * Retrieves active groups as options for select form elements.
   */
  public function getGroups() {
    $groups = array();
    $query = civicrm_api3('Group', 'get', array(
      'is_active' => 1,
      'option.limit'   => 0,
      'return'         => 'id,name'
    ));
    foreach ($query['values'] as $group) {
      $groups[$group['id']] = $group['name'];
    }
    return $groups;
  }

  /**
   * Retrieves active groups used as postal mailing lists within the system as
   * options for select form elements.
   */
  public function getPostinfoGroups() {
    return $this->getGroups();
  }

  /**
   * Retrieves active groups used as donation receipt requester lists within the
   * system as options for select form elements.
   */
  public function getDonationReceiptGroups() {
    return $this->getGroups();
  }

  /**
   * Retrieves campaigns as options for select elements.
   */
  public function getCampaigns() {
    $campaigns = array();
    $query = civicrm_api3('Campaign', 'get', array(
      'option.limit' => 0,
      'return' => array(
        'id',
        'title',
      )
    ));
    foreach ($query['values'] as $campaign) {
      $campaigns[$campaign['id']] = $campaign['title'];
    }
    return $campaigns;
  }

}
