<?php

namespace Civi\Twingle\Shop\Exceptions;

use Civi\Twingle\Exceptions\BaseException as BaseException;

/**
 * A simple custom exception that indicates a problem within the Line Items
 */
class LineItemException extends BaseException {

  public const ERROR_CODE_CONTRIBUTION_NOT_FOUND = "contribution_not_found";

}
