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

/**
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_Twingle_Form_Settings extends CRM_Core_Form {

  /**
   * @var array<string>
   *   List of all settings options.
   */
  public static $SETTINGS_LIST = [
      'twingle_prefix',
      'twingle_use_sepa',
      'twingle_dont_use_reference',
      'twingle_protect_recurring',
      'twingle_protect_recurring_activity_type',
      'twingle_protect_recurring_activity_subject',
      'twingle_protect_recurring_activity_status',
      'twingle_protect_recurring_activity_assignee',
      'twingle_use_shop',
      'twingle_access_key',
  ];

  /**
   * @inheritdoc
   */
  public function buildQuickForm(): void {
    // Set redirect destination.
    $this->controller->_destination = CRM_Utils_System::url('civicrm/admin/settings/twingle', 'reset=1');

    $this->add(
        'text',
        'twingle_prefix',
        E::ts('Twingle ID Prefix')
    );

    $this->add(
        'checkbox',
        'twingle_use_sepa',
        E::ts('Use CiviSEPA')
    );

    $this->add(
      'checkbox',
      'twingle_dont_use_reference',
      E::ts('Use CiviSEPA generated reference')
    );

    $this->add(
        'select',
        'twingle_protect_recurring',
        E::ts('Protect Recurring Contributions'),
        CRM_Twingle_Config::getRecurringProtectionOptions()
    );

    $this->add(
        'select',
        'twingle_protect_recurring_activity_type',
        E::ts('Activity Type'),
        $this->getOptionValueList('activity_type', [0])
    );

    $this->add(
        'text',
        'twingle_protect_recurring_activity_subject',
        E::ts('Subject'),
        ['class' => 'huge']
    );

    $this->add(
        'select',
        'twingle_protect_recurring_activity_status',
        E::ts('Status'),
        $this->getOptionValueList('activity_status')
    );

    $this->addEntityRef(
      'twingle_protect_recurring_activity_assignee',
      E::ts('Assigned To'),
      [
        'api' => [
          'params' => [
            'contact_type' => ['IN' => ['Individual', 'Organization']],
            'check_permissions' => 0,
          ],
        ],
      ]
    );

    $this->add(
      'checkbox',
      'twingle_use_shop',
      E::ts("Use Twingle Shop Integration")
    );

    $this->add(
      'text',
      'twingle_access_key',
      E::ts("Twingle Access Key")
    );

    $this->addButtons(array(
      array (
          'type'      => 'submit',
          'name'      => E::ts('Save'),
          'isDefault' => TRUE,
      )
    ));

    // set defaults
    foreach (self::$SETTINGS_LIST as $setting) {
      $this->setDefaults([
        $setting => Civi::settings()->get($setting),
      ]);
    }

    parent::buildQuickForm();
  }

  /**
   * Custom form validation, as some fields are mandatory only when others are active.
   * @return bool
   */
  public function validate() {
    parent::validate();

    // if activity creation is active, make sure the fields are set
    $protection_mode = $this->_submitValues['twingle_protect_recurring'] ?? NULL;
    if ($protection_mode == CRM_Twingle_Config::RCUR_PROTECTION_ACTIVITY) {
      foreach ([
        'twingle_protect_recurring_activity_type',
        'twingle_protect_recurring_activity_subject',
        'twingle_protect_recurring_activity_status',
        'twingle_protect_recurring_activity_assignee',
      ] as $activity_field) {
        if (NULL !== ($this->_submitValues[$activity_field] ?? NULL)) {
          $this->_errors[$activity_field] = E::ts('This is required for activity creation');
        }
      }
    }

    // Twingle Access Key is required if Shop Integration is enabled
    if (
      CRM_Utils_Array::value('twingle_use_shop', $this->_submitValues) &&
      !CRM_Utils_Array::value('twingle_access_key', $this->_submitValues, FALSE)
    ) {
      $this->_errors['twingle_access_key'] = E::ts("An Access Key is required to enable Twingle Shop Integration");
    }

    return (0 == count($this->_errors));
  }

  /**
   * @inheritdoc
   */
  public function postProcess(): void {
    $values = $this->exportValues();

    // store settings
    foreach (self::$SETTINGS_LIST as $setting) {
      Civi::settings()->set($setting, $values[$setting] ?? NULL);
    }

    parent::postProcess();
  }

  /**
   * Get a list of option group items
   * @param string $group_id
   *   Group ID or name.
   * @param array<int> $reserved
   * @return array<int|string, string> list of ID(value) => label
   * @throws \CRM_Core_Exception
   */
  protected function getOptionValueList(string $group_id, array $reserved = [0, 1]): array {
    $list = ['' => E::ts('-select-')];
    $query = civicrm_api3('OptionValue', 'get', [
      'option_group_id' => $group_id,
      'option.limit'    => 0,
      'is_active'       => 1,
      'is_reserved'     => ['IN' => $reserved],
      'return'          => 'value,label',
    ]);
    foreach ($query['values'] as $value) {
      $list[$value['value']] = $value['label'];
    }
    return $list;
  }

}
