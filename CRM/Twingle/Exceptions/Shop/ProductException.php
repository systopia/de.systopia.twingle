<?php

/**
 * A simple custom exception that indicates a problem within the
 * CRM_Twingle_Product class
 */
class CRM_Twingle_Exceptions_Shop_ProductException extends CRM_Twingle_Exceptions_Shop_BaseException {
  public const ERROR_CODE_ATTRIBUTE_WRONG_DATA_TYPE = "attribute_wrong_data_type";
}
