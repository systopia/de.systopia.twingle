<?php
// phpcs:disable
use CRM_Twingle_Exceptions_Shop_ApiCallError as TwingleApiCallError;
use CRM_Twingle_Exceptions_Shop_ShopException as TwingleShopException;
use CRM_Twingle_Exceptions_Shop_ProductException as TwingleShopProductException;
use CRM_Twingle_ExtensionUtil as E;
use CRM_Twingle_Shop_ApiCall as TwingleApiCall;
use CRM_Twingle_Shop_DAO_TwingleProduct as TwingleShopProduct;
// phpcs:enable

class CRM_Twingle_Shop_BAO_TwingleShop extends CRM_Twingle_Shop_DAO_TwingleShop {

  /**
   * @var array $shops
   *  Cache Twingle Projects of type 'shop' retrieved via API
   */
  public static array $shops;

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
   */
  public function __construct() {
    parent::__construct();

    // Get TwingleApiCall singleton
    $this->twingleApi = TwingleApiCall::singleton();
  }

  /**
   * Create Twingle Shop
   *
   * @param $project_identifier string
   *  Alphanumerical project identifier (like 'tw620214349ac97')
   *
   * @return void
   *
   * @throws \CRM_Twingle_Exceptions_Shop_ShopException
   * @throws \CRM_Twingle_Exceptions_Shop_ApiCallError
   */
  public function create(string $project_identifier, int $id = Null) {
    $hook = $id ? 'edit' : 'create';

    if ($id) {
      $this->id = $id;
      // Find numerical_project_id in database
      try {
        $dao = \CRM_Twingle_Shop_DAO_TwingleShop::findById($this->id);
        $this->numerical_project_id = $dao->numerical_project_id;
      }
      catch (Exception $e) {
        throw new TwingleShopException(
          E::ts('Could not find Twingle Shop with id %1.',
            ['1' => $this->id]),
          TwingleShopException::ERROR_CODE_COULD_NOT_FIND_SHOP_IN_DB
        );
      }
    }
    else {
      // Fetch the numerical project id from Twingle API.
      $this->fetchNumericalProjectId($project_identifier);
    }

    CRM_Utils_Hook::pre($hook, 'TwingleShop', $this->id);
    $this->save();
    CRM_Utils_Hook::post($hook, 'TwingleShop', $this->id, $instance);
  }

  /**
   * Fetch Twingle Shop products from Twingle
   *
   * @return array
   *   array of CRM_Twingle_Shop_BAO_Product
   *
   * @throws TwingleApiCallError
   * @throws TwingleShopProductException
   */
  public function fetchProducts(): array {

    // Fetch products from Twingle API
    $products = $this->twingleApi->get(
      'project',
      $this->numerical_project_id,
      'products',
    );

    // Instantiate Shop objects from retrieved data
    foreach ($products as $product) {
      $this->products[$product['id']] = new TwingleShopProduct($product);
      $this->products[$product['id']]->load($product);
    }

    return $this->products;
  }

  /**
   * Creates Twingle Shop as a price field in CiviCRM.
   *
   *  Price field ID
   *
   * @throws \CRM_Core_Exception
   */
  public function createPriceSet() {
    $price_set_data = [
      //'title' =>
    ];
    $price_set = civicrm_api3('PriceSet', 'create', $price_set_data);
  }

  /**
   * Retrieves the numerical project ID of this shop from db.
   *
   * @param string $project_identifier
   *  Alphanumerical project identifier (like 'tw620214349ac97')
   *
   * @throws \CRM_Twingle_Exceptions_Shop_ShopException
   * @throws \CRM_Twingle_Exceptions_Shop_ApiCallError
   */
  private function fetchNumericalProjectId(string $project_identifier) {

    // Establish connection, if not already connected
    if (!$this->twingleApi->isConnected) {
      $this->twingleApi->connect();
    }

    if (!isset($this->shops)) {
      $this->getShops();
    }

    // Set Shop ID
    foreach (self::$shops as $shop) {
      if (isset($shop['identifier']) && $shop['identifier'] == $project_identifier) {
        $this->numerical_project_id = $shop['id'];
      }
    }

    // Throw an Exception if this Twingle Project is not of type 'shop'
    if (!isset($this->numProjectId)) {
      throw new TwingleShopException(
        E::ts('This Twingle Project is not a shop.'),
        TwingleShopException::ERROR_CODE_NOT_A_SHOP,
      );
    }
  }

  /**
   * Retrieves all Twingle projects of the type 'shop'.
   *
   * @throws TwingleShopException
   */
  private function getShops(): void {
    $organisationId = $this->twingleApi->organisationId;
    try {
      $projects = $this->twingleApi->get(
        'project',
        NULL,
        'by-organisation',
        $organisationId,
      );
      self::$shops = array_filter(
        $projects,
        fn($project) => isset($project['type']) && $project['type'] == 'shop'
      );
    }
    catch (Exception $e) {
      throw new TwingleShopException(
        E::ts('Could not retrieve Twingle projects from API.
          Please check your API credentials.'),
        TwingleShopException::ERROR_CODE_COULD_NOT_GET_PROJECTS,
      );
    }
  }

}
