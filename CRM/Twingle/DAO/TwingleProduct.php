<?php

/**
 * DAOs provide an OOP-style facade for reading and writing database records.
 *
 * DAOs are a primary source for metadata in older versions of CiviCRM (<5.74)
 * and are required for some subsystems (such as APIv3).
 *
 * This stub provides compatibility. It is not intended to be modified in a
 * substantive way. Property annotations may be added, but are not required.
 * @property string $id
 * @property string $external_id
 * @property string $price_field_id
 * @property string $twingle_shop_id
 * @property string $created_at
 * @property string $updated_at
 */
class CRM_Twingle_DAO_TwingleProduct extends CRM_Twingle_DAO_Base {

  /**
   * Required by older versions of CiviCRM (<5.74).
   * @var string
   */
  public static $_tableName = 'civicrm_twingle_product';

}
