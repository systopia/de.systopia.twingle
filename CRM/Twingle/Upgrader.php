<?php
use CRM_Twingle_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Twingle_Upgrader extends CRM_Twingle_Upgrader_Base {

  /**
   * Installer script
   */
  public function install() {
    // create a DB table for the twingle profiles
    $this->executeSqlFile('sql/civicrm_twingle_profile.sql');

    // add a default profile
    CRM_Twingle_Profile::createDefaultProfile()->saveProfile();
  }

  /**
   * Example: Run an external SQL script when the module is uninstalled.
   *
  public function uninstall() {
   $this->executeSqlFile('sql/myuninstall.sql');
  }

  /**
   * Copy financial_type_id setting to new setting financial_type_id_recur.
   */
  public function upgrade_4000() {
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
  public function upgrade_5011() {
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
  public function upgrade_5140() {
    $this->ctx->log->info('Converting twingle profiles.');

    // create a DB table for the twingle profiles
    $this->executeSqlFile('sql/civicrm_twingle_profile.sql');

    // migrate the current profiles
    if ($profiles_data = Civi::settings()->get('twingle_profiles')) {
      foreach ($profiles_data as $profile_name => $profile_data) {
        $profile = new CRM_Twingle_Profile($profile_name, $profile_data);
        $data = json_encode($profile->getData());
        CRM_Core_DAO::executeQuery(
          "INSERT IGNORE INTO civicrm_twingle_profile(name,config,last_access,access_counter) VALUES (%1, %2, NOW(), 0)",
          [
            1 => [$profile_name, 'String'],
            2 => [$data, 'String']
          ]);
      }
    }

    return TRUE;
  }

  /**
   * Add the "generic" payment instrument with default values to all profiles
   * to avoid "Payment method could not be matched to existing payment
   * instrument." error.
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_5150() {
    $this->ctx->log->info('Add "generic" payment instrument to all profiles.');

    $profiles = CRM_Twingle_Profile::getProfiles();
    if ($profiles) {
      foreach ($profiles as $profile) {
        if (!$profile->getAttribute('pi_generic', False)) {
          $profile->setAttribute('pi_generic', 1);
          $profile->setAttribute('pi_generic_status', 1);
          $profile->saveProfile();
        }
      }
    }
    
    return TRUE;
  }
}
