<?php
use CRM_Twingle_ExtensionUtil as E;

return [
  'name' => 'TwingleProduct',
  'table' => 'civicrm_twingle_product',
  'class' => 'CRM_Twingle_DAO_TwingleProduct',
  'getInfo' => fn() => [
    'title' => E::ts('Twingle Product'),
    'title_plural' => E::ts('Twingle Products'),
    'description' => E::ts('This table contains the Twingle Product data.'),
    'log' => FALSE,
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Unique TwingleProduct ID'),
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'external_id' => [
      'title' => E::ts('External ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('The ID of this product in the Twingle database'),
    ],
    'price_field_id' => [
      'title' => E::ts('Price Field ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => E::ts('FK to Price Field'),
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'twingle_shop_id' => [
      'title' => E::ts('Twingle Shop ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => E::ts('FK to Twingle Shop'),
      'entity_reference' => [
        'entity' => 'TwingleShop',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'created_at' => [
      'title' => E::ts('Created At'),
      'sql_type' => 'datetime',
      'input_type' => 'Select Date',
      'required' => TRUE,
      'description' => E::ts('Timestamp of when the product was created in the database'),
    ],
    'updated_at' => [
      'title' => E::ts('Updated At'),
      'sql_type' => 'datetime',
      'input_type' => 'Select Date',
      'required' => TRUE,
      'description' => E::ts('Timestamp of when the product was last updated in the database'),
    ],
  ],
];
