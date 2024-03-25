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
 * A simple custom exception that indicates a problem within the
 * CRM_Twingle_Profile class
 */
class ProfileException extends BaseException {

  public const ERROR_CODE_PROFILE_NOT_FOUND = 'profile_not_found';
  public const ERROR_CODE_DEFAULT_PROFILE_NOT_FOUND = 'default_profile_not_found';
  public const ERROR_CODE_COULD_NOT_SAVE_PROFILE = 'could_not_save_profile';
  public const ERROR_CODE_COULD_NOT_RESET_PROFILE = 'could_not_reset_profile';
  public const ERROR_CODE_COULD_NOT_DELETE_PROFILE = 'could_not_delete_profile';
  public const ERROR_CODE_UNKNOWN_PROFILE_ATTRIBUTE = 'unknown_profile_attribute';

}
