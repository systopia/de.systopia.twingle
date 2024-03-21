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

use CRM_Twingle_ExtensionUtil as E;

/**
 * A simple custom exception class that indicates a problem within a class
 * of the Twingle API extension.
 */
class BaseException extends \Exception {

  /**
   * @var string
   */
  protected $code;
  protected string $log_message;

  /**
   * BaseException Constructor
   * @param string $message
   *   Error message
   * @param string $error_code
   *   A meaningful error code
   * @param \Throwable $previous
   *   A previously thrown exception to include.
   */
  public function __construct(string $message = '', string $error_code = '', \Throwable $previous = NULL) {
    parent::__construct($message, 1, $previous);
    $this->log_message = '' !== $message ? E::LONG_NAME . ': ' . $message : '';
    $this->code = $error_code;
  }

  /**
   * Returns the error message, but with the extension name prefixed.
   * @return string
   */
  public function getLogMessage() {
    return $this->log_message;
  }

  /**
   * Returns the error code.
   * @return string
   */
  public function getErrorCode() {
    return $this->code;
  }

}
