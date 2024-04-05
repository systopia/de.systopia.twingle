<?php
/*
 * Copyright (C) 2024 SYSTOPIA GmbH
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

declare(strict_types = 1);

namespace Civi\Twingle\Exceptions;

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
  public function __construct(
    string $affected_field_name,
    string $message = '',
    string $error_code = '',
    ?\Throwable $previous = NULL
  ) {
    parent::__construct($message, $error_code, $previous);
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
