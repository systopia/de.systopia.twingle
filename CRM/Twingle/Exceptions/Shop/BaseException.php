<?php

use CRM_Twingle_ExtensionUtil as E;

/**
 * A simple custom exception class that indicates a problem within a class
 * of the Twingle Shop integration.
 */
class CRM_Twingle_Exceptions_Shop_BaseException extends Exception {

  public string $error_code;

  /**
   * BaseException Constructor
   * @param string $message
   *  Error message
   * @param string $error_code
   *  A meaningful error code
   */
  public function __construct(string $message = "", string $error_code = "") {
    $message = !empty($message) ? E::LONG_NAME . ': ' . $message : "";
    parent::__construct($message, 1);
    $this->error_code = $error_code;
  }

}
