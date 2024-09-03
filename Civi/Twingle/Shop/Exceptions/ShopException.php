<?php

namespace Civi\Twingle\Shop\Exceptions;

use Civi\Twingle\Exceptions\BaseException as BaseException;

/**
 * A simple custom exception that indicates a problem within the
 * CRM_Twingle_Shop class
 */
class ShopException extends BaseException {

  public const ERROR_CODE_NOT_A_SHOP = "not_a_shop";
  public const ERROR_CODE_COULD_NOT_GET_PROJECTS = "could_not_get_projects";
  public const ERROR_CODE_COULD_NOT_FIND_SHOP_IN_DB = "could_not_find_shop_in_db";
  public const ERROR_CODE_PRICE_SET_ALREADY_EXISTS = "price_set_already_exists";
  public const ERROR_CODE_COULD_NOT_CREATE_PRICE_SET = "price_set_creation_failed";
  public const ERROR_CODE_PRICE_SET_NOT_FOUND = "price_set_not_found";
  public const ERROR_CODE_COULD_NOT_DELETE_PRICE_SET = "price_set_deletion_failed";
  public const ERROR_CODE_ATTRIBUTE_WRONG_DATA_TYPE = "attribute_wrong_data_type";

}
