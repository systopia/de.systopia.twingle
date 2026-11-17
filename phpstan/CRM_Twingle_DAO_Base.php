<?php

/**
 * Declares CRM_Twingle_DAO_Base for PHPStan's symbol discovery (see
 * scanFiles in phpstan.neon.dist).
 *
 * At runtime CRM_Twingle_DAO_Base is aliased to CRM_Core_DAO_Base by an
 * autoloader in twingle.civix.php. PHPStan doesn't execute that autoloader,
 * so without this declaration it doesn't know the methods and properties
 * inherited by the DAO and BAO classes.
 */
abstract class CRM_Twingle_DAO_Base extends CRM_Core_DAO_Base {

}
