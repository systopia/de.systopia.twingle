<?php

/**
 * A simple custom exception that indicates a problem within the
 * CRM_Twingle_Shop class
 */
class CRM_Twingle_Exceptions_Shop_ShopException extends CRM_Twingle_Exceptions_BaseException {

  public const ERROR_CODE_NOT_A_SHOP = "not_a_shop";
  public const ERROR_CODE_COULD_NOT_GET_PROJECTS = "could_not_get_projects";
  public const ERROR_CODE_COULD_NOT_FIND_SHOP_IN_DB = "could_not_find_shop_in_db";

}
