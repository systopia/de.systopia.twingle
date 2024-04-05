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

class CRM_Twingle_Page_Profiles extends CRM_Core_Page {

  public function run():void {
    CRM_Utils_System::setTitle(E::ts('Twingle API Profiles'));
    $profiles = [];
    foreach (CRM_Twingle_Profile::getProfiles() as $profile_name => $profile) {
      $profiles[$profile_name]['name'] = $profile_name;
      foreach (CRM_Twingle_Profile::allowedAttributes() as $attribute) {
        $profiles[$profile_name][$attribute] = $profile->getAttribute($attribute);
      }
    }
    $this->assign('profiles', $profiles);
    $this->assign('profile_stats', CRM_Twingle_Profile::getProfileStats());

    parent::run();
  }

}
