<?php

namespace Civi\Twingle\Shop\BAO;

// phpcs:disable
use CRM_Twingle_ExtensionUtil as E;
use Civi\Twingle\Shop\DAO\TwingleShop as TwingleShopDAO;
use Civi\Twingle\Shop\BAO\TwingleProduct as TwingleProductBAO;
use Civi\Twingle\Shop\ApiCall;
use Civi\Twingle\Shop\Exceptions\ShopException;
use Civi\Twingle\Shop\Exceptions\ProductException;
use Exception;
use function Civi\Twingle\Shop\Utils\filter_attributes;
use function Civi\Twingle\Shop\Utils\convert_str_to_int;
use function Civi\Twingle\Shop\Utils\validate_data_types;
// phpcs:enable

require_once E::path() . '/Civi/Twingle/Shop/Utils/TwingleShopUtils.php';

class TwingleShop extends TwingleShopDAO {

  public const ALLOWED_ATTRIBUTES = [
    'id' => \CRM_Utils_Type::T_INT,
    'project_identifier' => \CRM_Utils_Type::T_STRING,
    'numerical_project_id' => \CRM_Utils_Type::T_INT,
    'name' => \CRM_Utils_Type::T_STRING,
    'price_set_id' => \CRM_Utils_Type::T_INT,
    'financial_type_id' => \CRM_Utils_Type::T_INT,
  ];

  public const STR_TO_INT_CONVERSION = [
    'id',
    'numerical_project_id',
    'price_set_id',
    'financial_type_id',
  ];

  /**
   * @var array $products
   *  Array of Twingle Shop products (Cache)
   */
  public $products;

  /**
   * FK to Financial Type
   *
   * @var int
   */
  public $financial_type_id;

  /**
   * TwingleShop constructor
   */
  public function __construct() {
    parent::__construct();
    // Get TwingleApiCall singleton
    $this->twingleApi = ApiCall::singleton();
  }

  /**
   * Get Twingle Shop from database by its project identifier
   * (like 'tw620214349ac97')
   *
   * @param string $project_identifier
   *   Twingle project identifier
   *
   * @return TwingleShop
   *
   * @throws ShopException
   * @throws \Civi\Twingle\Shop\Exceptions\ApiCallError
   * @throws \CRM_Core_Exception
   */
  public static function findByProjectIdentifier(string $project_identifier) {
    $shop = new TwingleShop();
    $shop->get('project_identifier', $project_identifier);
    if (!$shop->id) {
      $shop->fetchDataFromTwingle($project_identifier);
    }
    else {
      $shop->price_set_id = civicrm_api3('PriceSet', 'getvalue',
        ['return' => 'id', 'name' => $project_identifier]);
    }
    return $shop;
  }

  /**
   * Load Twingle Shop data
   *
   * @param array $shop_data
   *   Array with shop data
   *
   * @return void
   *
   * @throws ShopException
   */
  public function load(array $shop_data): void {
    // Filter for allowed attributes
    filter_attributes($shop_data, self::ALLOWED_ATTRIBUTES);

    // Convert string to int
    try {
      convert_str_to_int($shop_data, self::STR_TO_INT_CONVERSION);
    }
    catch (Exception $e) {
      throw new ShopException($e->getMessage(), ShopException::ERROR_CODE_ATTRIBUTE_WRONG_DATA_TYPE);
    }

    // Validate data types
    try {
      validate_data_types($shop_data, self::ALLOWED_ATTRIBUTES);
    }
    catch (Exception $e) {
      throw new ShopException($e->getMessage(), ShopException::ERROR_CODE_ATTRIBUTE_WRONG_DATA_TYPE);
    }

    // Set attributes
    foreach ($shop_data as $key => $value) {
      $this->$key = $value;
    }
  }

  /**
   * Get attributes
   *
   * @return array
   */
  function getAttributes(): array {
    return [
      'id' => $this->id,
      'project_identifier' => $this->project_identifier,
      'numerical_project_id' => $this->numerical_project_id,
      'name' => $this->name,
      'price_set_id' => $this->price_set_id,
      'financial_type_id' => $this->financial_type_id,
    ];
  }

  /**
   * Add Twingle Shop
   *
   * @param string $mode
   *  'create' or 'edit'
   * @return array
   * @throws \Civi\Twingle\Shop\Exceptions\ShopException
   */
  public function add($mode = 'create') {

    // Try to lookup object in database
    try {
      $dao = TwingleShopDAO::executeQuery("SELECT * FROM civicrm_twingle_shop WHERE project_identifier = %1",
        [1 => [$this->project_identifier, 'String']]);
      if ($dao->fetch()) {
        $this->load($dao->toArray());
      }
    } catch (\Civi\Core\Exception\DBQueryException $e) {
      throw new ShopException(
        E::ts('Could not find TwingleShop in database: ' . $e->getMessage()),
        ShopException::ERROR_CODE_COULD_NOT_FIND_SHOP_IN_DB);
    }

    // Register pre-hook
    $twingle_shop_values = $this->getAttributes();
    \CRM_Utils_Hook::pre($mode, 'TwingleShop', $this->id, $twingle_shop_values);
    $this->load($twingle_shop_values);

    // Save object to database
    $result = $this->save();

    // Register post-hook
    \CRM_Utils_Hook::post($mode, 'TwingleShop', $this->id, $instance);

    return $result->toArray();
  }

  /**
   * Delete object by deleting the associated PriceSet and letting the foreign
   * key constraint do the rest.
   *
   * @throws \Civi\Twingle\Shop\Exceptions\ShopException*
   * @throws \Civi\Twingle\Shop\Exceptions\ProductException
   */
  function deleteByConstraint() {
    // Register post-hook
    $twingle_shop_values = $this->getAttributes();
    \CRM_Utils_Hook::pre('delete', 'TwingleShop', $this->id, $twingle_shop_values);
    $this->load($twingle_shop_values);

    // Delete associated products
    $this->deleteProducts();

    // Try to get single PriceSet
    try {
      civicrm_api3('PriceSet', 'getsingle',
        ['id' => $this->price_set_id]);
    }
    catch (\CRM_Core_Exception $e) {
      if ($e->getMessage() != 'Expected one PriceSet but found 0') {
        throw new ShopException(
          E::ts('Could not find associated PriceSet: ' . $e->getMessage()),
          ShopException::ERROR_CODE_PRICE_SET_NOT_FOUND);
      }
      else {
        // If no PriceSet is found, we can simply delete the TwingleShop
        return $this->delete();
      }
    }

    // Deleting the associated PriceSet will also lead to the deletion of this
    // TwingleShop because of the foreign key constraint and cascading.
    try {
      $result = civicrm_api3('PriceSet', 'delete',
        ['id' => $this->price_set_id]);
    } catch (\CRM_Core_Exception $e) {
      throw new ShopException(
        E::ts('Could not delete associated PriceSet: ' . $e->getMessage()),
        ShopException::ERROR_CODE_COULD_NOT_DELETE_PRICE_SET);
    }

    // Register post-hook
    \CRM_Utils_Hook::post('delete', 'TwingleShop', $this->id, $instance);

    // Free global arrays associated with this object
    $this->free();

    return $result['is_error'] == 0;
  }

  /**
   * Fetch Twingle Shop products from Twingle
   *
   * @return array
   *   array of CRM_Twingle_Shop_BAO_Product
   *
   * @throws \Civi\Twingle\Shop\Exceptions\ApiCallError;
   * @throws \Civi\Twingle\Shop\Exceptions\ProductException;
   * @throws \Civi\Core\Exception\DBQueryException
   * @throws \CRM_Core_Exception
   */
  public function fetchProducts(): array {
    // Establish connection, if not already connected
    if (!$this->twingleApi->isConnected) {
      $this->twingleApi->connect();
    }

    // Fetch products from Twingle API
    $products_from_twingle = $this->twingleApi->get(
      'project',
      $this->numerical_project_id,
      'products',
    );

    // Fetch products from database
    if ($this->id) {
      $products_from_db = $this->getProducts();

      $products_from_twingle = array_reduce($products_from_twingle, function($carry, $product) {
        $carry[$product['id']] = $product;
        return $carry;
      }, []);

      foreach ($products_from_db as $product) {
        /* @var TwingleProductBAO $product */

        // Find orphaned products which are in the database but not in Twingle
        $found = array_key_exists($product->external_id, $products_from_twingle);
        if (!$found) {
          $product->is_orphaned = TRUE;
        }
        else {
          // Complement with data from Twingle
          $product->complementWithDataFromTwingle($products_from_twingle[$product->external_id]);
          // Mark outdated products which have a newer version in Twingle
          $product->checkOutdated($products_from_twingle[$product->external_id]);
        }
        $this->products[] = $product;
      }
    }

    // Create array with external_id as key
    $products = array_reduce($this->products ?? [], function($carry, $product) {
      $carry[$product->external_id] = $product;
      return $carry;
    }, []);

    // Add new products from Twingle
    foreach ($products_from_twingle as $product_from_twingle) {
      $found = array_key_exists($product_from_twingle['id'], $products);
      if (!$found) {
        $product = new TwingleProduct();
        $product->load(TwingleProduct::renameTwingleAttrs($product_from_twingle));
        $product->twingle_shop_id = $this->id;
        $this->products[] = $product;
      }
    }
    return $this->products;
  }

  /**
   * Get associated products.
   *
   * @return array[Civi\Twingle\Shop\BAO\TwingleProduct]
   * @throws \Civi\Core\Exception\DBQueryException
   * @throws \Civi\Twingle\Shop\Exceptions\ProductException
   */
  public function getProducts() {
    $products = [];

    $result = TwingleProductBAO::executeQuery(
      "SELECT * FROM civicrm_twingle_product WHERE twingle_shop_id = %1",
      [1 => [$this->id, 'Integer']]
    );

    while ($result->fetch()) {
      $product = new TwingleProductBAO();
      $product->load($result->toArray());
      $products[] = $product;
    }

    return $products;
  }

  /**
   * Creates Twingle Shop as a price set in CiviCRM.
   *
   * @param string $mode
   *  'create' or 'edit'
   * @throws \Civi\Twingle\Shop\Exceptions\ShopException
   */
  public function createPriceSet($mode = 'create') {

    // Define mode
    $mode = $this->price_set_id ? 'edit' : 'create';

    // Check if PriceSet for this Shop already exists
    try {
      $price_set = civicrm_api3('PriceSet', 'get', [
        'name' => $this->project_identifier,
      ]);
      if ($price_set['count'] > 0 && $mode == 'create') {
        throw new ShopException(
          E::ts('PriceSet for this Twingle Shop already exists.'),
          ShopException::ERROR_CODE_PRICE_SET_ALREADY_EXISTS,
        );
      }
      elseif ($price_set['count'] == 0 && $mode == 'edit') {
        throw new ShopException(
          E::ts('PriceSet for this Twingle Shop does not exist and cannot be edited.'),
          ShopException::ERROR_CODE_PRICE_SET_NOT_FOUND,
        );
      }
    } catch (\CRM_Core_Exception $e) {
      throw new ShopException(
        E::ts('Could not check if PriceSet for this TwingleShop already exists.'),
        ShopException::ERROR_CODE_PRICE_SET_NOT_FOUND,
      );
    }

    // Create PriceSet
    $price_set_data = [
      'name' => $this->project_identifier,
      'title' => "$this->name ($this->project_identifier)",
      'is_active' => 1,
      'extends' => 2,
      'financial_type_id' => $this->financial_type_id,
    ];
    // Set id if in edit mode
    if ($mode == 'edit') {
      $price_set_data['id'] = $this->price_set_id;
    }
    try {
      $price_set = civicrm_api4('PriceSet', 'create',
        ['values' => $price_set_data])->first();
      $this->price_set_id = (int) $price_set['id'];
    } catch (\CRM_Core_Exception $e) {
      throw new ShopException(
        E::ts('Could not create PriceSet for this TwingleShop.'),
        ShopException::ERROR_CODE_COULD_NOT_CREATE_PRICE_SET,
      );
    }
  }

  /**
   * Retrieves the numerical project ID and the name of this shop from Twingle.
   *
   * @throws \Civi\Twingle\Shop\Exceptions\ShopException
   * @throws \Civi\Twingle\Shop\Exceptions\ApiCallError
   */
  private function fetchDataFromTwingle() {

    // Establish connection, if not already connected
    if (!$this->twingleApi->isConnected) {
      $this->twingleApi->connect();
    }

    // Get shops from Twingle if not cached
    $shops = \Civi::cache('long')->get('twingle_shops');
    if (empty($shops)) {
      $this::fetchShops($this->twingleApi);
      $shops = \Civi::cache('long')->get('twingle_shops');
    }

    // Set Shop ID and name
    foreach ($shops as $shop) {
      if (isset($shop['identifier']) && $shop['identifier'] == $this->project_identifier) {
        $this->numerical_project_id = $shop['id'];
        $this->name = $shop['name'];
      }
    }

    // Throw an Exception if this Twingle Project is not of type 'shop'
    if (!isset($this->numerical_project_id)) {
      throw new ShopException(
        E::ts('This Twingle Project is not a shop.'),
        ShopException::ERROR_CODE_NOT_A_SHOP,
      );
    }
  }

  /**
   * Retrieves all Twingle projects of the type 'shop'.
   *
   * @throws \Civi\Twingle\Shop\Exceptions\ShopException
   */
  static private function fetchShops(ApiCall $api): void {
    $organisationId = $api->organisationId;
    try {
      $projects = $api->get(
        'project',
        NULL,
        'by-organisation',
        $organisationId,
      );
      $shops = array_filter(
        $projects,
        function($project) {
          return isset($project['type']) && $project['type'] == 'shop';
        }
      );
      \Civi::cache('long')->set('twingle_shops', $shops);
    }
    catch (Exception $e) {
      throw new ShopException(
        E::ts('Could not retrieve Twingle projects from API.
          Please check your API credentials.'),
        ShopException::ERROR_CODE_COULD_NOT_GET_PROJECTS,
      );
    }
  }

  /**
   * Deletes all associated products.
   *
   * @return void
   * @throws \Civi\Twingle\Shop\Exceptions\ProductException
   */
  public function deleteProducts() {
    try {
      $products = $this->getProducts();
    } catch (\Civi\Core\Exception\DBQueryException $e) {
      throw new ProductException(
        E::ts('Could not retrieve associated products: ' . $e->getMessage()),
        ProductException::ERROR_CODE_COULD_NOT_GET_PRODUCTS
      );
    }
    try {
      foreach ($products as $product) {
        $product->delete();
      }
    }
    catch (ProductException $e) {
      throw new ProductException(
        E::ts('Could not delete associated products: ' . $e->getMessage()),
        ProductException::ERROR_CODE_COULD_NOT_DELETE_PRICE_SET
      );
    }
  }

}
