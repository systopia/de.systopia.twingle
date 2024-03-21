<?php
/*------------------------------------------------------------+
| SYSTOPIA Twingle Integration                                |
| Copyright (C) 2019 SYSTOPIA                                 |
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
 * Collection of upgrade steps.
 */
class CRM_Twingle_Upgrader extends CRM_Extension_Upgrader_Base {

  /**
   * Installer script
   */
  public function install(): void {
    // create a DB table for the twingle profiles
    $this->executeSqlFile('sql/civicrm_twingle_profile.sql');

    // add a default profile
    CRM_Twingle_Profile::createDefaultProfile()->saveProfile();
  }

  /**
   * Example: Run an external SQL script when the module is uninstalled.
   *
   * public function uninstall() {
   * $this->executeSqlFile('sql/myuninstall.sql');
   * }
   *
   * /**
   * Copy financial_type_id setting to new setting financial_type_id_recur.
   */
  public function upgrade_4000(): bool {
    $this->ctx->log->info('Applying update 4000: Copying Financial type to new setting Financial type (recurring).');
    foreach (CRM_Twingle_Profile::getProfiles() as $profile) {
      $profile->setAttribute('financial_type_id_recur', $profile->getAttribute('financial_type_id'));
      $profile->saveProfile();
    }
    return TRUE;
  }

  /**
   * Convert serialized settings from objects to arrays.
   *
   * @link https://civicrm.org/advisory/civi-sa-2019-21-poi-saved-search-and-report-instance-apis
   */
  public function upgrade_5011(): bool {
    // Do not use CRM_Core_BAO::getItem() or Civi::settings()->get().
    // Extract and unserialize directly from the database.
    $twingle_profiles_query = CRM_Core_DAO::executeQuery("
        SELECT `value`
          FROM `civicrm_setting`
        WHERE `name` = 'twingle_profiles';");
    if ($twingle_profiles_query->fetch()) {
      $profiles = unserialize($twingle_profiles_query->value);
      Civi::settings()->set('twingle_profiles', (array) $profiles);
    }

    return TRUE;
  }

  /**
   * Upgrading to 1.4.0 needs to convert the profiles into the new infrastructure
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_5140(): bool {
    $this->ctx->log->info('Converting twingle profiles.');

    // create a DB table for the twingle profiles
    $this->executeSqlFile('sql/civicrm_twingle_profile.sql');

    // migrate the current profiles
    if (is_array($profiles_data = Civi::settings()->get('twingle_profiles'))) {
      foreach ($profiles_data as $profile_name => $profile_data) {
        $profile = new CRM_Twingle_Profile($profile_name, $profile_data);
        $data = json_encode($profile->getData());
        CRM_Core_DAO::executeQuery(<<<SQL
          INSERT IGNORE INTO civicrm_twingle_profile(name,config,last_access,access_counter) VALUES (%1, %2, NOW(), 0)
          SQL,
          [
            1 => [$profile_name, 'String'],
            2 => [$data, 'String'],
          ]);
      }
    }

    return TRUE;
  }

  /**
   * Upgrade to 1.5.0
   *
   * - Activate mapping of `purpose` and `user_extra_field` to notes in each existing profile to
   *   maintain default behavior after making the fields optional.
   *
   * @return bool
   * @throws \Civi\Core\Exception\DBQueryException
   * @throws \Civi\Twingle\Exceptions\ProfileException
   */
  public function upgrade_5150(): bool {
    $this->ctx->log->info('Activate mapping of `purpose` and `user_extra_field` to notes in each existing profile.');

    foreach (CRM_Twingle_Profile::getProfiles() as $profile) {
      $profile_changed = FALSE;
      /** @phpstan-var array<string> $contribution_notes */
      $contribution_notes = $profile->getAttribute('map_as_contribution_notes', []);
      /** @phpstan-var array<string> $contact_notes */
      $contact_notes = $profile->getAttribute('map_as_contact_notes', []);
      if (!in_array('purpose', $contribution_notes, TRUE)) {
        $profile->setAttribute('map_as_contribution_notes', array_merge($contribution_notes, ['purpose']));
        $profile_changed = TRUE;
      }
      if (!in_array('user_extrafield', $contact_notes, TRUE)) {
        $profile->setAttribute('map_as_contact_notes', array_merge($contact_notes, ['user_extrafield']));
        $profile_changed = TRUE;
      }
      if ($profile_changed) {
        $profile->saveProfile();
      }
    }

    return TRUE;
  }

  /**
   * The Upgrade to 1.5.1 creates the tables civicrm_twingle_product and
   * civicrm_twingle_shop.
   *
   * @return TRUE on success
   */
  public function upgrade_5151() {
    $this->ctx->log->info('Creating tables for Twingle Shop.');
    $this->executeSqlFile('sql/civicrm_twingle_shop.sql');
    return TRUE;
  }
}
