<?php /** @noinspection ALL */

use CRM_TwingleCampaign_ExtensionUtil as E;
use CRM_Twingle_Exceptions_Shop_ApiCallError as TwingleApiCallError;

/**
 * This class communicates with the Twingle API via cURL.
 * To keep the overhead of initialization low, this class is implemented as
 * a singleton. Please use CRM_Twingle_TwingleApiCall::singleton() to retrieve
 * an instance.
 */
class CRM_Twingle_Shop_ApiCall {

  /**
   * Twingle API url
   */
  const BASE_URL = '.twingle.de/api';

  /**
   * The transfer protocol
   */
  const PROTOCOL = 'https://';

  /**
   * The singleton object
   * @var CRM_Twingle_Shop_ApiCall $singleton
   */
  public static CRM_Twingle_Shop_ApiCall $singleton;

  /**
   * Your Twingle API token.
   * You can request an API token from Twingle support: <hilfe@twingle.de>
   * @var string $apiToken
   */
  private string $apiToken;

  /**
   * The ID of your organization in the Twingle database.
   * Automatically retrieved by sending a request with the associated API token.
   * @var int $organisationId
   */
  public int $organisationId;

  /**
   * This boolean indicates whether the connection was successful.
   *
   * @var bool $isConnected
   */
  public bool $isConnected;

  /**
   * Limit the number of items requested per API call.
   * @var int $limit
   */
  public int $limit = 40;

  /**
   * Header for cURL request.
   * @var string[] $header
   */
  private array $header;

  /**
   * Protected TwingleApiCall constructor.
   * Use CRM_Twingle_TwingleApiCall::singleton() instead.
   *
   * @throws API_Exception
   */
  protected function __construct() {
    $this->isConnected = FALSE;
  }

  /**
   * Returns CRM_Twingle_Shop_ApiCall singleton
   *
   * @return CRM_Twingle_Shop_ApiCall
   */
  public static function singleton(): CRM_Twingle_Shop_ApiCall {
    if (empty(self::$singleton)) {
      self::$singleton = new CRM_Twingle_Shop_ApiCall();
      return self::$singleton;
    }
    else {
      return self::$singleton;
    }
  }

  /**
   * Try to connect to the Twingle API and retrieve the organisation ID.
   *
   * @return bool
   *  returns TRUE if the connection was successfully established
   *
   * @throws TwingleApiCallError
   */
  public function connect(): bool {

    $this->isConnected = FALSE;

    try {
      // Get api token from settings
      $apiToken = Civi::settings()->get("twingle_access_key");
      if (empty($apiToken)) {
        throw new TypeError();
      }
      $this->apiToken = $apiToken;
    } catch (TypeError $e) {
      throw new TwingleApiCallError(
        E::ts("Could not find Twingle API token"),
        TwingleApiCallError::ERROR_CODE_API_TOKEN_MISSING,
      );
    }

    $this->header = [
      "x-access-code: $this->apiToken",
      'Content-Type: application/json',
    ];

    $url = self::PROTOCOL . 'organisation' . self::BASE_URL . "/";
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $this->header);

    $response = json_decode(curl_exec($curl), TRUE);

    if (empty($response)) {
      curl_close($curl);
      throw new TwingleApiCallError(
        E::ts("Call to Twingle API failed. Please check your api token."),
        TwingleApiCallError::ERROR_CODE_CONNECTION_FAILED,
      );
    }
    self::check_response_and_close($response, $curl);

    $this->organisationId = array_column($response, 'id')[0];
    $this->isConnected = TRUE;
    return $this->isConnected;
  }

  /**
   * Check response on cURL
   *
   * @param $response
   *  the cURL response to check
   * @param $curl
   *  the cURL resource
   *
   * @return bool
   *  returns true if the response is fine
   *
   * @throws TwingleApiCallError
   */
  protected static function check_response_and_close($response, $curl) {

    $curl_status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($response == FALSE) {
      throw new TwingleApiCallError(
        E::ts('GET curl failed'),
        TwingleApiCallError::ERROR_CODE_GET_REQUEST_FAILED,
      );
    }
    if ($curl_status_code == 404) {
      throw new TwingleApiCallError(
        E::ts('http status code 404 (not found)'),
        TwingleApiCallError::ERROR_CODE_404,
      );
    }
    elseif ($curl_status_code == 500) {
      throw new TwingleApiCallError(
        E::ts('https status code 500 (internal error)'),
        TwingleApiCallError::ERROR_CODE_500,
      );
    }

    return TRUE;
  }

  /**
   * Sends a GET cURL and returns the result array.
   *
   * @param $entity
   *  Twingle entity
   *
   * @param null $params
   *  Optional GET parameters
   *
   * @return array
   *  Returns the result array of the or FALSE, if the cURL failed
   * @throws TwingleApiCallError
   */
  public function get(
    string $entity,
    string $entityId = NULL,
    string $endpoint = NULL,
    string $endpointId = NULL,
    array $params = NULL
  ): array {

    // Throw an error, if connection is not yet established
    if ($this->isConnected == FALSE) {
      throw new TwingleApiCallError(
        E::ts("Connection not yet established. Use connect() method."),
        TwingleApiCallError::ERROR_CODE_NOT_CONNECTED,
      );
    }

    // Build URL and initialize cURL
    $url = self::PROTOCOL . $entity . self::BASE_URL;
    if (!empty($entityId)) {
      $url .= "/$entityId";
    }
    if (!empty($endpoint)) {
      $url .= "/$endpoint";
    }
    if (!empty($endpointId)) {
      $url .= "/$endpointId";
    }
    if (!empty($params)) {
      $url .= '?' . http_build_query($params);
    }
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $this->header);

    // Execute cURL
    $response = json_decode(curl_exec($curl), TRUE);
    self::check_response_and_close($response, $curl);

    return $response;
  }
}
