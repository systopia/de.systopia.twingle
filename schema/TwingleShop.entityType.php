<?php
use CRM_Twingle_ExtensionUtil as E;

return [
  'name' => 'TwingleShop',
  'table' => 'civicrm_twingle_shop',
  'class' => 'CRM_Twingle_DAO_TwingleShop',
  'getInfo' => fn() => [
    'title' => E::ts('Twingle Shop'),
    'title_plural' => E::ts('Twingle Shops'),
    'description' => E::ts('This table contains the Twingle Shop data. Each Twingle Shop is linked to a corresponding Price Set.'),
    'log' => FALSE,
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Unique TwingleShop ID'),
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'project_identifier' => [
      'title' => E::ts('Project Identifier'),
      'sql_type' => 'varchar(32)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => E::ts('Twingle Project Identifier'),
    ],
    'numerical_project_id' => [
      'title' => E::ts('Numerical Project ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Numerical Twingle Project Identifier'),
    ],
    'price_set_id' => [
      'title' => E::ts('Price Set ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => E::ts('FK to Price Set'),
      'entity_reference' => [
        'entity' => 'PriceSet',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'name' => [
      'title' => E::ts('Name'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => E::ts('name of the shop'),
    ],
  ],
];
