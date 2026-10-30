<?php
/*
 * Copyright (C) 2026 SYSTOPIA GmbH
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation in version 3.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

use CRM_Twingle_ExtensionUtil as E;

// Do not register if Identity Tracker is not installed / active
if (!\CRM_Extension_System::singleton()->getMapper()->isActiveModule('identitytracker')) {
  return [];
}

return [
  [
    'name' => 'OptionValue__contact_id_history_type__person_id',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'contact_id_history_type',
        'label' => E::ts('Person ID'),
        'value' => 'person_id',
        'name' => 'person_id',
        'is_reserved' => TRUE,
        'is_active' => TRUE,
        'icon' => 'fa-user-circle',
      ],
      'match' => [
        'option_group_id',
        'name',
      ],
    ],
  ],
];
