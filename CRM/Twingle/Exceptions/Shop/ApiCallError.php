<?php

/**
 * A simple custom exception that indicates a problem within the
 * CRM_Twingle_Shop_ApiCall class
 */
class CRM_Twingle_Exceptions_Shop_ApiCallError extends CRM_Twingle_Exceptions_BaseException {
  public const ERROR_CODE_API_TOKEN_MISSING = "api_token_missing";
  public const ERROR_CODE_CONNECTION_FAILED = "connection_failed";
  public const ERROR_CODE_NOT_CONNECTED = "not_connected";
  public const ERROR_CODE_GET_REQUEST_FAILED = "get_request_failed";
  public const ERROR_CODE_404 = "404";
  public const ERROR_CODE_500 = "500";

}
