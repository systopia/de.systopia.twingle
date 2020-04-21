<?php
/*------------------------------------------------------------+
| SYSTOPIA Twingle Integration                                |
| Copyright (C) 2020 SYSTOPIA                                 |
| Author: B. Endres (endres@systopia.de)                      |
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

class CRM_Twingle_Config {

  const RCUR_PROTECTION_OFF       = 0;
  const RCUR_PROTECTION_EXCEPTION = 1;
  const RCUR_PROTECTION_ACTIVITY  = 2;

  /**
   * Get the options for protecting a recurring contribution linked Twingle
   *  against ending or cancellation (because Twingle would keep on collecting them)
   *
   * @return array
   */
  public static function getRecurringProtectionOptions() {
    return [
        self::RCUR_PROTECTION_OFF       => E::ts("No"),
        self::RCUR_PROTECTION_EXCEPTION => E::ts("Raise Exception"),
        self::RCUR_PROTECTION_ACTIVITY  => E::ts("Create Activity"),
    ];
  }
}
