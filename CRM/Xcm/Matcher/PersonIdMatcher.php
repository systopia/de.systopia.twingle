<?php
/*-------------------------------------------------------+
| Twingle CiviCRM Integration                            |
| Copyright (C) 2026 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

declare(strict_types = 1);

// Guard against missing parent class when Identity Tracker is not installed/active
// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
if (!class_exists('CRM_Xcm_Matcher_IdTrackerMatcher')) {
  return;
}
// phpcs:enable

/**
 * Will use the ID tracker to match the field 'person_id'
 */
class CRM_Xcm_Matcher_PersonIdMatcher extends CRM_Xcm_Matcher_IdTrackerMatcher {

  public const IDENTIFIER_TYPE = 'person_id';

  public function __construct() {
    parent::__construct(self::IDENTIFIER_TYPE, ['person_id']);
  }

}
