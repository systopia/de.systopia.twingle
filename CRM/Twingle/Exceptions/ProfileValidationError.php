<?php

namespace CRM\Twingle\Exceptions;

/**
 * A simple custom error indicating a problem with the validation of the
 * CRM_Twingle_Profile
 */
class ProfileValidationError extends BaseException {

  private string $affected_field_name;
  public const ERROR_CODE_PROFILE_VALIDATION_FAILED = 'profile_validation_failed';
  public const ERROR_CODE_PROFILE_VALIDATION_WARNING = 'profile_validation_warning';

  /**
   * ProfileValidationError Constructor
   * @param string $affected_field_name
   *  The name of the profile field which caused the exception
   * @param string $message
   *  Error message
   * @param string $error_code
   *  A meaningful error code
   */
  public function __construct(string $affected_field_name, string $message = '', string $error_code = '') {
    parent::__construct($message, $error_code);
    $this->affected_field_name = $affected_field_name;
  }

  /**
   * Returns the name of the profile field that caused the exception.
   * @return string
   */
  public function getAffectedFieldName() {
    return $this->affected_field_name;
  }

}
