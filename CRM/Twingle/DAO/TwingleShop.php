<?php

/**
 * DAOs provide an OOP-style facade for reading and writing database records.
 *
 * DAOs are a primary source for metadata in older versions of CiviCRM (<5.74)
 * and are required for some subsystems (such as APIv3).
 *
 * This stub provides compatibility. It is not intended to be modified in a
 * substantive way. Property annotations may be added, but are not required.
 * @property int|string|null $id
 * @property string $project_identifier
 * @property int|string $numerical_project_id
 * @property int|string|null $price_set_id
 * @property string $name
 */
class CRM_Twingle_DAO_TwingleShop extends CRM_Twingle_DAO_Base {

  /**
   * Required by older versions of CiviCRM (<5.74).
   * @var string
   */
  public static $_tableName = 'civicrm_twingle_shop';

}
