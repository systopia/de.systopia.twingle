<?php

namespace Civi\Twingle\Shop\Exceptions;

use Civi\Twingle\Exceptions\BaseException as BaseException;

/**
 * A simple custom exception that indicates a problem within the
 * CRM_Twingle_Product class
 */

class ProductException extends BaseException {
  public const ERROR_CODE_ATTRIBUTE_WRONG_DATA_TYPE = 'attribute_wrong_data_type';
  public const ERROR_CODE_PRICE_FIELD_ALREADY_EXISTS = 'price_field_already_exists';
  public const ERROR_CODE_PRICE_FIELD_VALUE_NOT_FOUND = 'price_field_value_not_found';
  public const ERROR_CODE_PRICE_FIELD_NOT_FOUND = 'price_field_not_found';
  public const ERROR_CODE_PRICE_SET_NOT_FOUND = 'price_set_not_found';
  public const ERROR_CODE_COULD_NOT_CREATE_PRICE_FIELD = 'price_field_creation_failed';
  public const ERROR_CODE_COULD_NOT_CREATE_PRICE_FIELD_VALUE = 'price_field_value_creation_failed';
  public const ERROR_CODE_COULD_NOT_DELETE_PRICE_FIELD = 'price_field_deletion_failed';
  public const ERROR_CODE_COULD_NOT_DELETE_PRICE_FIELD_VALUE = 'price_field_value_deletion_failed';
  public const ERROR_CODE_PRICE_FIELD_STILL_EXISTS = 'price_field_still_exists';
  public const ERROR_CODE_COULD_NOT_CREATE_PRODUCT = 'product_creation_failed';
  public const ERROR_CODE_COULD_NOT_GET_PRODUCTS = 'product_retrieval_failed';
  public const ERROR_CODE_COULD_NOT_DELETE_PRICE_SET = 'price_set_deletion_failed';
}
