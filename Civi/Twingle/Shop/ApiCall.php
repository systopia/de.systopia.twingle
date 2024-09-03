<?php /** @noinspection ALL */

namespace Civi\Twingle\Shop;

use CRM_TwingleCampaign_ExtensionUtil as E;
use Civi\Twingle\Shop\Exceptions\ApiCallError;

/**
 * This class communicates with the Twingle API via cURL.
 * To keep the overhead of initialization low, this class is implemented as
 * a singleton. Please use CRM_Twingle_TwingleApiCall::singleton() to retrieve
 * an instance.
 */
class ApiCall {

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
   * @var \Civi\Twingle\Shop\ApiCall $singleton
   */
  public static ApiCall $singleton;

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
   * The cURL wrapper
   * @var \Civi\Twingle\Shop\CurlWrapper $curlWrapper
   */
  private CurlWrapper $curlWrapper;

  /**
   * Protected TwingleApiCall constructor.
   * Use \Civi\Twingle\ApiCall::singleton() instead.
   * @param \Civi\Twingle\Shop\CurlWrapper $curlWrapper
   */
  protected function __construct(CurlWrapper $curlWrapper) {
    $this->curlWrapper = $curlWrapper;
    $this->isConnected = FALSE;
  }

  /**
   * Returns \Civi\Twingle\Shop\ApiCall singleton
   *
   * @param \Civi\Twingle\Shop\CurlWrapper|null $curlWrapper
   *   Optional cURL wrapper for testing purposes
   * @return \Civi\Twingle\Shop\ApiCall
   */
  public static function singleton(CurlWrapper $curlWrapper = null): ApiCall {
    if (empty(self::$singleton)) {
      $curlWrapper = $curlWrapper ?? new CurlWrapper();
      self::$singleton = new ApiCall($curlWrapper);
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
   * @throws \Civi\Twingle\Shop\Exceptions\ApiCallError
   */
  public function connect(): bool {

    $this->isConnected = FALSE;

    try {
      // Get api token from settings
      $apiToken = \Civi::settings()->get("twingle_access_key");
      if (empty($apiToken)) {
        throw new \TypeError();
      }
      $this->apiToken = $apiToken;
    } catch (\TypeError $e) {
      throw new ApiCallError(
        E::ts("Could not find Twingle API token"),
        ApiCallError::ERROR_CODE_API_TOKEN_MISSING,
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
      throw new ApiCallError(
        E::ts("Call to Twingle API failed. Please check your api token."),
        ApiCallError::ERROR_CODE_CONNECTION_FAILED,
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
   * @throws \Civi\Twingle\Shop\Exceptions\ApiCallError
   */
  protected static function check_response_and_close($response, $curl) {

    $curl_status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($response == FALSE) {
      throw new ApiCallError(
        E::ts('GET curl failed'),
        ApiCallError::ERROR_CODE_GET_REQUEST_FAILED,
      );
    }
    if ($curl_status_code == 404) {
      throw new ApiCallError(
        E::ts('http status code 404 (not found)'),
        ApiCallError::ERROR_CODE_404,
      );
    }
    elseif ($curl_status_code == 500) {
      throw new ApiCallError(
        E::ts('https status code 500 (internal error)'),
        ApiCallError::ERROR_CODE_500,
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
   * @throws \Civi\Twingle\Shop\Exceptions\ApiCallError
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
      throw new ApiCallError(
        E::ts("Connection not yet established. Use connect() method."),
        ApiCallError::ERROR_CODE_NOT_CONNECTED,
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

/**
 * A simple wrapper for the cURL functions to allow for easier testing.
 */
class CurlWrapper {
  public function init($url) {
    return curl_init($url);
  }

  public function setopt($ch, $option, $value) {
    return curl_setopt($ch, $option, $value);
  }

  public function exec($ch) {
    return curl_exec($ch);
  }

  public function getinfo($ch, $option) {
    return curl_getinfo($ch, $option);
  }

  public function close($ch) {
    curl_close($ch);
  }
}
