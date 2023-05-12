<?php

use CRM_Twingle_Exceptions_Shop_ApiCallError as TwingleApiCallError;
use CRM_Twingle_Exceptions_Shop_ShopException as TwingleShopException;
use CRM_Twingle_Exceptions_Shop_ProductException as TwingleShopProductException;
use CRM_Twingle_ExtensionUtil as E;
use CRM_Twingle_Shop_ApiCall as TwingleApiCall;
use CRM_Twingle_Shop_Product as TwingleShopProduct;

class CRM_Twingle_Shop_Shop {

  /**
   * @var array $shops
   *  Cache Twingle Projects of type "shop" retrieved via API
   */
  public static array $shops;

  /**
   * @var CRM_Twingle_Shop_ApiCall $twingleApi
   *  Object to communicate via cURL with Twingle API
   */
  private CRM_Twingle_Shop_ApiCall $twingleApi;

  /**
   * @var string $identifier
   *  Alphanumerical project identifier (like "tw620214349ac97")
   */
  public string $identifier;

  /**
   * @var int $numProjectId
   *  Numerical project ID
   */
  public int $numProjectId;

  /**
   * @var array $products
   *  Array of Twingle Shop products
   */
  public array $products;

  /**
   * @var int $priceSetId
   *  ID of associated price set for this Shop
   */
  public int $priceSetId;

  /**
   * TwingleShop constructor
   *
   * @param string $identifier
   *  Alphanumerical project identifier (like "tw620214349ac97")
   *
   * @throws TwingleApiCallError
   * @throws TwingleShopException
   */
  public function __construct(string $identifier) {

    // Get TwingleApiCall singleton
    $this->twingleApi = TwingleApiCall::singleton();

    // Establish connection, if not already connected
    if (!$this->twingleApi->isConnected) {
      $this->twingleApi->connect();
    }

    // Fetch the numerical project id from Twingle API.
    // This id is necessary for building the cURL requests later.
    $this->fetchNumericalProjectId($identifier);
  }

  /**
   * Fetch Twingle Shop products from Twingle
   *
   * @return array
   *   array of CRM_Twingle_Shop_Product
   *
   * @throws TwingleApiCallError
   * @throws TwingleShopProductException
   */
  public function fetchProducts(): array {

    // Fetch products from Twingle API
    $products = $this->twingleApi->get(
      "project",
      $this->numProjectId,
      "products",
    );

    // Instantiate Shop objects from retrieved data
    foreach ($products as $product) {
      $this->products[$product["id"]] = new TwingleShopProduct($product);
      $this->products[$product["id"]]->load($product);
    }

    return $this->products;
  }

  /**
   * Creates Twingle Shop as a price field in CiviCRM.
   *
   * @return int
   *  Price field ID
   */
  public function createPriceSet(): int {
    // TODO
    return 1;
  }

  /**
   * Retrieves the numerical project ID of this shop.
   *
   * @param string $identifier
   *  Alphanumerical project identifier (like "tw620214349ac97")
   *
   * @throws TwingleShopException
   */
  private function fetchNumericalProjectId(string $identifier): void {
    if (!isset($this->shops)) {
      $this->getShops();
    }

    // Set Shop ID
    foreach (self::$shops as $shop) {
      if (isset($shop["identifier"]) && $shop["identifier"] == $identifier) {
        $this->numProjectId = $shop["id"];
      }
    }

    // Throw an Exception if this Twingle Project is not of type "shop"
    if (!isset($this->numProjectId)) {
      throw new TwingleShopException(
        E::ts("This Twingle Project is not a shop."),
        TwingleShopException::ERROR_CODE_NOT_A_SHOP,
      );
    }
  }

  /**
   * Retrieves all Twingle projects of the type "shop".
   *
   * @throws TwingleShopException
   */
  private function getShops(): void {
    $organisationId = $this->twingleApi->organisationId;
    try {
      $projects = $this->twingleApi->get(
        "project",
        NULL,
        "by-organisation",
        $organisationId,
      );
      self::$shops = array_filter(
        $projects,
        fn($project) => isset($project["type"]) && $project["type"] == "shop"
      );
    }
    catch (Exception $e) {
      throw new TwingleShopException(
        E::ts("Could not retrieve Twingle projects from API.
          Please check your API credentials."),
        TwingleShopException::ERROR_CODE_COULD_NOT_GET_PROJECTS,
      );
    }
  }

}
