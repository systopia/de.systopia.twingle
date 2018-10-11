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

/*
* Settings metadata file
*/
return array(
  'twingle_use_sepa' => array(
    'group_name' => 'de.systopia.twingle',
    'group' => 'de.systopia.twingle',
    'name' => 'twingle_use_sepa',
    'type' => 'Boolean',
    'quick_form_type' => 'YesNo',
    'html_type' => 'radio',
    'title' => 'Use CiviSEPA',
    'default' => 0,
    'add' => '4.6',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Whether to provide CiviSEPA functionality for manual debit payment method. This requires the CiviSEPA (org.project60.sepa) extension be installed.',
  ),
);