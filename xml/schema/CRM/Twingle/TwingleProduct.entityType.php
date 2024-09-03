<?php
// This file declares a new entity type. For more details, see "hook_civicrm_entityTypes" at:
// https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
return [
  [
    'name' => 'TwingleProduct',
    'class' => 'Civi\Twingle\Shop\DAO\TwingleProduct',
    'table' => 'civicrm_twingle_product',
  ],
];
