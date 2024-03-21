<?php

namespace Civi\Twingle\Shop\BAO;

use Civi\Api4\PriceField;
use Civi\Twingle\Shop\DAO\TwingleProduct as TwingleProductDAO;
use Civi\Twingle\Shop\DAO\TwingleShop as TwingleShopDAO;
use Civi\Twingle\Shop\Exceptions\ProductException;
use Civi\Twingle\Shop\Exceptions\ShopException;
use CRM_Core_Exception;
use CRM_Core_Transaction;
use CRM_Twingle_ExtensionUtil as E;
use CRM_Utils_Type;
use function Civi\Twingle\Shop\Utils\convert_int_to_bool;
use function Civi\Twingle\Shop\Utils\convert_str_to_date;
use function Civi\Twingle\Shop\Utils\convert_str_to_int;
use function Civi\Twingle\Shop\Utils\convert_null_to_int;
use function Civi\Twingle\Shop\Utils\filter_attributes;
use function Civi\Twingle\Shop\Utils\validate_data_types;

require_once E::path() . '/Civi/Twingle/Shop/Utils/TwingleShopUtils.php';

/**
 * TwingleProduct BAO class.
 * This class is used to implement the logic for the TwingleProduct entity.
 */
class TwingleProduct extends TwingleProductDAO {

  /**
   * Name of this product.
   */
  public $name;

  /**
   * Is this product active?
   */
  public $is_active;

  /**
   * Price of this product.
   */
  public $price;

  /**
   *  Sort order of this product.
   */
  public $sort;

  /**
   * Short description of this Product.
   */
  public $text;

  /**
   * Long description of this Product.
   */
  public $description;

  /**
   * ID of the corresponding Twingle Shop.
   */
  public $project_id;

  /**
   * ID of the financial type of this product.
   */
  public $financial_type_id;

  /**
   * Timestamp of the last update in Twingle db.
   */
  public $tw_updated_at;

  /**
   * The values of this attributes can be 0.
   * (For filtering purposes)
   */
  protected const CAN_BE_ZERO = [
    "price",
    "sort",
  ];

  /**
   * Attributes that need to be converted to int.
   */
  protected const STR_TO_INT_CONVERSION = [
    "id",
    "twingle_shop_id",
    "financial_type_id",
    "price_field_id",
    "project_id",
    "external_id",
    "tw_updated_at",
    "tw_created_at",
    "price",
    "sort",
  ];

  /**
   * Attributes that need to be converted to boolean.
   */
  protected const INT_TO_BOOL_CONVERSION = [
    "is_active",
  ];

  /**
   * String to date conversion.
   */
  protected const STR_TO_DATE_CONVERSION = [
    "created_at",
    "updated_at",
  ];

  /**
   * Attributes that need to be converted from NULL to int.
   */
  protected const NULL_TO_INT_CONVERSION = [
    "price",
  ];

  /**
   * Allowed product attributes.
   * Attributes that we currently don't support are commented out.
   */
  protected const ALLOWED_ATTRIBUTES = [
    "id" => CRM_Utils_Type::T_INT,
    "external_id" => CRM_Utils_Type::T_INT,
    "name" => CRM_Utils_Type::T_STRING,
    "is_active" => CRM_Utils_Type::T_BOOLEAN,
    "description" => CRM_Utils_Type::T_STRING,
    "price" => CRM_Utils_Type::T_INT,
    "created_at" => CRM_Utils_Type::T_INT,
    "tw_created_at" => CRM_Utils_Type::T_INT,
    "updated_at" => CRM_Utils_Type::T_INT,
    "tw_updated_at" => CRM_Utils_Type::T_INT,
    "is_orphaned" => CRM_Utils_Type::T_BOOLEAN,
    "is_outdated" => CRM_Utils_Type::T_BOOLEAN,
    "project_id" => CRM_Utils_Type::T_INT,
    "sort" => CRM_Utils_Type::T_INT,
    "financial_type_id" => CRM_Utils_Type::T_INT,
    "twingle_shop_id" => CRM_Utils_Type::T_INT,
    "price_field_id" => CRM_Utils_Type::T_INT,
    # "text" => \CRM_Utils_Type::T_STRING,
    # "images" => \CRM_Utils_Type::T_STRING,
    # "categories" = \CRM_Utils_Type::T_STRING,
    # "internal_id" => \CRM_Utils_Type::T_STRING,
    # "has_zero_price" => \CRM_Utils_Type::T_BOOLEAN,
    # "name_plural" => \CRM_Utils_Type::T_STRING,
    # "max_count" => \CRM_Utils_Type::T_INT,
    # "has_textinput" => \CRM_Utils_Type::T_BOOLEAN,
    # "count" => \CRM_Utils_Type::T_INT,
  ];

  /**
   * Change attribute names to match the database column names.
   *
   * @param array $values
   *  Array with product data from Twingle API
   *
   * @return array
   */
  public static function renameTwingleAttrs(array $values) {
    $new_values = [];
    foreach ($values as $key => $value) {
      // replace 'id' with 'external_id'
      if ($key == 'id') {
        $key = 'external_id';
      }
      // replace 'updated_at' with 'tw_updated_at'
      if ($key == 'updated_at') {
        $key = 'tw_updated_at';
      }
      // replace 'created_at' with 'tw_created_at'
      if ($key == 'created_at') {
        $key = 'tw_created_at';
      }
      $new_values[$key] = $value;
    }
    return $new_values;
  }

  /**
   * Load product data.
   *
   * @param array $product_data
   *  Array with product data
   *
   * @return void
   *
   * @throws ProductException
   * @throws \Exception
   */
  public function load(array $product_data): void {
    // Filter for allowed attributes
    filter_attributes(
      $product_data,
      self::ALLOWED_ATTRIBUTES,
      self::CAN_BE_ZERO,
    );

    // Amend data from corresponding PriceFieldValue
    if (isset($product_data['price_field_id'])) {
      try {
        $price_field_value = civicrm_api3('PriceFieldValue', 'getsingle', [
          'price_field_id' => $product_data['price_field_id'],
        ]);
      }
      catch (CRM_Core_Exception $e) {
        throw new ProductException(
          E::ts("Could not find PriceFieldValue for Twingle Product ['id': %1, 'external_id': %2]: %3",
            [
              1 => $product_data['id'],
              2 => $product_data['external_id'],
              3 => $e->getMessage(),
            ]),
          ProductException::ERROR_CODE_PRICE_FIELD_VALUE_NOT_FOUND);
      }
      $product_data['name'] = $product_data['name'] ?? $price_field_value['label'];
      $product_data['price'] = $product_data['price'] ?? $price_field_value['amount'];
      $product_data['financial_type_id'] = $product_data['financial_type_id'] ?? $price_field_value['financial_type_id'];
      $product_data['is_active'] = $product_data['is_active'] ?? $price_field_value['is_active'];
      $product_data['sort'] = $product_data['sort'] ?? $price_field_value['weight'];
      $product_data['description'] = $product_data['description'] ?? $price_field_value['description'];
    }

    // Change data types
    try {
      convert_str_to_int($product_data, self::STR_TO_INT_CONVERSION);
      convert_int_to_bool($product_data, self::INT_TO_BOOL_CONVERSION);
      convert_str_to_date($product_data, self::STR_TO_DATE_CONVERSION);
      convert_null_to_int($product_data, self::NULL_TO_INT_CONVERSION);
    }
    catch (\Exception $e) {
      throw new ProductException($e->getMessage(), ProductException::ERROR_CODE_ATTRIBUTE_WRONG_DATA_TYPE);
    }

    // Validate data types
    try {
      validate_data_types($product_data, self::ALLOWED_ATTRIBUTES);
    }
    catch (\Exception $e) {
      throw new ProductException($e->getMessage(), ProductException::ERROR_CODE_ATTRIBUTE_WRONG_DATA_TYPE);
    }

    // Set attributes
    foreach ($product_data as $key => $value) {
      $this->$key = $value;
    }
  }

  /**
   * Creates a price field to represents this product in CiviCRM.
   *
   * @param string $mode
   *  'create' or 'edit'
   * @throws \Civi\Twingle\Shop\Exceptions\ProductException
   */
  public function createPriceField() {
    // Define mode for PriceField
    $mode = $this->price_field_id ? 'edit' : 'create';
    $action = $mode == 'create' ? 'create' : 'update';

    // Check if PriceSet for this Shop already exists
    try {
      $price_field = civicrm_api3('PriceField', 'get', [
        'name' => 'tw_product_' . $this->external_id,
      ]);
      if ($price_field['count'] > 0 && $mode == 'create') {
        throw new ProductException(
          E::ts('PriceField for this Twingle Product already exists.'),
          ProductException::ERROR_CODE_PRICE_FIELD_ALREADY_EXISTS,
        );
      } elseif ($price_field['count'] == 0 && $mode == 'edit') {
        throw new ProductException(
          E::ts('PriceField for this Twingle Product does not exist and cannot be edited.'),
          ProductException::ERROR_CODE_PRICE_FIELD_NOT_FOUND,
        );
      }
    }
    catch (CRM_Core_Exception $e) {
      throw new ProductException(
        E::ts('Could not check if PriceField for this Twingle Product already exists.'),
        ProductException::ERROR_CODE_PRICE_FIELD_NOT_FOUND,
      );
    }

    // Try to find corresponding price set via TwingleShop
    try {
      $shop = civicrm_api3('TwingleShop', 'getsingle', [
        'id' => $this->twingle_shop_id,
      ]);
      $this->price_set_id = (int) $shop['price_set_id'];
    }
    catch (CRM_Core_Exception $e) {
      throw new ProductException(
        E::ts('Could not find PriceSet for this Twingle Product.'),
        ProductException::ERROR_CODE_PRICE_SET_NOT_FOUND,
      );
    }

    // Create PriceField
    $price_field_data = [
      'price_set_id' => $this->price_set_id,
      'name' => 'tw_product_' . $this->external_id,
      'label' => $this->name,
      'is_active' => $this->is_active,
      'weight' => $this->sort,
      'html_type' => 'Text',
      'is_required' => false,
    ];
    // Add id if in edit mode
    if ($mode == 'edit') {
      $price_field_data['id'] = $this->price_field_id;
    }
    try {
      $price_field = civicrm_api4(
        'PriceField',
        $action,
        ['values' => $price_field_data],
      )->first();
      $this->price_field_id = (int) $price_field['id'];
    }
    catch (CRM_Core_Exception $e) {
      throw new ProductException(
        E::ts('Could not create PriceField for this Twingle Product: %1',
          [1 => $e->getMessage()]),
        ProductException::ERROR_CODE_COULD_NOT_CREATE_PRICE_FIELD);
    }

    // Try to find existing PriceFieldValue if in edit mode
    $price_field_value = NULL;
    if ($mode == 'edit') {
      try {
        $price_field_value = civicrm_api3('PriceFieldValue', 'getsingle', [
          'price_field_id' => $this->price_field_id,
        ]);
      }
      catch (CRM_Core_Exception $e) {
        throw new ProductException(
          E::ts('Could not find PriceFieldValue for this Twingle Product: %1',
            [1 => $e->getMessage()]),
          ProductException::ERROR_CODE_PRICE_FIELD_VALUE_NOT_FOUND);
      }
    }

    // Create PriceFieldValue
    $price_field_value_data = [
      'price_field_id' => $this->price_field_id,
      'financial_type_id' => $this->financial_type_id,
      'label' => $this->name,
      'amount' => $this->price,
      'is_active' => $this->is_active,
      'description' => $this->description,
    ];
    // Add id if in edit mode
    if ($mode == 'edit' && $price_field_value) {
      $price_field_value_data['id'] = $price_field_value['id'];
    }
    try {
      civicrm_api4(
        'PriceFieldValue',
        $action,
        ['values' => $price_field_value_data],
      );
    }
    catch (CRM_Core_Exception $e) {
      throw new ProductException(
        E::ts('Could not create PriceFieldValue for this Twingle Product: %1',
          [1 => $e->getMessage()]),
        ProductException::ERROR_CODE_COULD_NOT_CREATE_PRICE_FIELD_VALUE);
    }
  }

  /**
   * Returns this TwingleProduct's attributes.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function getAttributes() {
    // Filter for allowed attributes
    return array_intersect_key(
        get_object_vars($this),
        $this::ALLOWED_ATTRIBUTES
      ) // Add financial type id of this product if it exists
      + ['financial_type_id' => $this->getFinancialTypeId()];
  }

  /**
   * Find TwingleProduct by its external ID.
   *
   * @param int $external_id
   *   External id of the product (by Twingle)
   *
   * @return TwingleProduct|null
    *   TwingleProduct object or NULL if not found
   *
   * @throws \Civi\Twingle\Shop\Exceptions\ProductException
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public static function findByExternalId($external_id) {
    $dao = TwingleShopDAO::executeQuery("SELECT * FROM civicrm_twingle_product WHERE external_id = %1",
      [1 => [$external_id, 'String']]);
    if ($dao->fetch()) {
      $product = new self();
      $product->load($dao->toArray());
      return $product;
    }
    return NULL;
  }

  /**
   * Add Twingle Product
   *
   * @param string $mode
   *  'create' or 'edit'
   * @return array
   * @throws \Civi\Twingle\Shop\Exceptions\ProductException
   * @throws \Exception
   */
  public function add($mode = 'create') {

    $tx = new CRM_Core_Transaction();

    // Define mode
    $mode = $this->id ? 'edit' : 'create';

    // Try to lookup object in database
    try {
      $dao = TwingleShopDAO::executeQuery("SELECT * FROM civicrm_twingle_product WHERE external_id = %1",
        [1 => [$this->external_id, 'String']]);
      if ($dao->fetch()) {
        $this->copyValues(array_merge($dao->toArray(), $this->getAttributes()));
      }
    }
    catch (\Civi\Core\Exception\DBQueryException $e) {
      throw new ProductException(
        E::ts('Could not find TwingleProduct in database: ' . $e->getMessage()),
        ShopException::ERROR_CODE_COULD_NOT_FIND_SHOP_IN_DB);
    }

    // Register pre-hook
    $twingle_product_values = $this->getAttributes();
    try {
      \CRM_Utils_Hook::pre($mode, 'TwingleProduct', $this->id, $twingle_product_values);
    } catch (\Exception $e) {
      $tx->rollback();
      throw $e;
    }
    $this->load($twingle_product_values);

    // Set latest tw_updated_at as new updated_at
    $this->updated_at = \CRM_Utils_Time::date('Y-m-d H:i:s', $this->tw_updated_at);

    // Convert created_at to date string
    $this->created_at = \CRM_Utils_Time::date('Y-m-d H:i:s', $this->created_at);

    // Save object to database
    try {
      $this->save();
    } catch (\Exception $e) {
      $tx->rollback();
      throw new ProductException(
        E::ts('Could not save TwingleProduct to database: ' . $e->getMessage()),
        ProductException::ERROR_CODE_COULD_NOT_CREATE_PRODUCT);
    }
    $result = self::findById($this->id);
    /* @var self $result */
    $this->load($result->getAttributes());

    // Register post-hook
    $twingle_product_values = $this->getAttributes();
    try {
      \CRM_Utils_Hook::post($mode, 'TwingleProduct', $this->id, $twingle_product_values);
    }
    catch (\Exception $e) {
      $tx->rollback();
      throw $e;
    }
    $this->load($twingle_product_values);

    return $result->toArray();
  }

  /**
   * Delete TwingleProduct along with associated PriceField and PriceFieldValue.
   *
   * @override \Civi\Twingle\Shop\DAO\TwingleProduct::delete
   * @throws \CRM_Core_Exception
   * @throws \Civi\Twingle\Shop\Exceptions\ProductException
   */
  function delete($useWhere = FALSE) {
    // Register post-hook
    $twingle_product_values = $this->getAttributes();
    \CRM_Utils_Hook::pre('delete', 'TwingleProduct', $this->id, $twingle_product_values);
    $this->load($twingle_product_values);

    // Delete TwingleProduct
    parent::delete($useWhere);

    // Register post-hook
    \CRM_Utils_Hook::post('delete', 'TwingleProduct', $this->id, $instance);

    // Free global arrays associated with this object
    $this->free();

    return true;
  }

  /**
   * Complements the data with the data that was fetched from Twingle.
   *
   * @throws \Civi\Twingle\Shop\Exceptions\ProductException
   */
  public function complementWithDataFromTwingle($product_from_twingle) {
    // Complement with data from Twingle
    $this->load([
      'project_id' => $product_from_twingle['project_id'],
      'tw_updated_at' => $product_from_twingle['updated_at'],
      'tw_created_at' => $product_from_twingle['created_at'],
    ]);
  }

  /**
   * Check if the product is outdated.
   *
   * @param $product_from_twingle
   *
   * @return void
   * @throws \Civi\Twingle\Shop\Exceptions\ProductException
   */
  public function checkOutdated($product_from_twingle) {
    // Mark outdated products which have a newer timestamp in Twingle
    if ($this->updated_at < intval($product_from_twingle['updated_at'])) {
      // Overwrite the product with the data from Twingle
      $this->load(self::renameTwingleAttrs($product_from_twingle));
      $this->is_outdated = TRUE;
    }
  }

  /**
   * Compare two products
   *
   * @param TwingleProduct $product_to_compare_with
   *   Product from database
   *
   * @return bool
   */
  public function equals($product_to_compare_with) {
    return
      $this->name === $product_to_compare_with->name &&
      $this->description === $product_to_compare_with->description &&
      $this->text === $product_to_compare_with->text &&
      $this->price === $product_to_compare_with->price &&
      $this->external_id === $product_to_compare_with->external_id;
  }

  /**
   * Returns the financial type id of this product.
   *
   * @return int|null
   * @throws \CRM_Core_Exception
   */
  public function getFinancialTypeId(): ?int {
    if (!empty($this->price_field_id)) {
      $price_set = \Civi\Api4\PriceField::get()
        ->addSelect('financial_type_id')
        ->addWhere('id', '=', $this->price_field_id)
        ->execute()
        ->first();
      return $price_set['financial_type_id'];
    }
    return NULL;
  }

  /**
   * Returns the price field value id of this product.
   *
   * @return int|null
   * @throws \CRM_Core_Exception
   */
  public function getPriceFieldValueId() {
    if (!empty($this->price_field_id)) {
      $price_field_value = \Civi\Api4\PriceFieldValue::get()
        ->addSelect('id')
        ->addWhere('price_field_id', '=', $this->price_field_id)
        ->execute()
        ->first();
      return $price_field_value['id'];
    }
    return NULL;
  }

  /**
   * Delete PriceField and PriceFieldValue of this TwingleProduct if they exist.
   *
   * @return void
   * @throws \Civi\Twingle\Shop\Exceptions\ProductException
   */
  public function deletePriceField(): void {
    // Before we can delete the PriceField we need to delete the associated
    // PriceFieldValue
    try {
      $result = civicrm_api3('PriceFieldValue', 'getsingle',
        ['price_field_id' => $this->price_field_id]);
    }
    catch (CRM_Core_Exception $e) {
      throw new ProductException(
        E::ts('An Error occurred while searching for the associated PriceFieldValue: ' . $e->getMessage()),
        ProductException::ERROR_CODE_PRICE_FIELD_VALUE_NOT_FOUND);
    }
    try {
      civicrm_api3('PriceFieldValue', 'delete', ['id' => $result['id']]);
    }
    catch (CRM_Core_Exception $e) {
      throw new ProductException(
        E::ts('Could not delete associated PriceFieldValue: ' . $e->getMessage()),
        ProductException::ERROR_CODE_COULD_NOT_DELETE_PRICE_FIELD_VALUE);
    }

    // Try to delete PriceField
    // If no PriceField is found, we assume that it has already been deleted
    try {
      civicrm_api3('PriceField', 'delete',
        ['id' => $this->price_field_id]);
    }
    catch (CRM_Core_Exception $e) {
      // Check if PriceField yet exists
      try {
        $result = civicrm_api3('PriceField', 'get',
          ['id' => $this->price_field_id]);
        // Throw exception if PriceField still exists
        if ($result['count'] > 0) {
          throw new ProductException(
            E::ts('PriceField for this Twingle Product still exists.'),
            ProductException::ERROR_CODE_PRICE_FIELD_STILL_EXISTS);
        }
      }
      catch (CRM_Core_Exception $e) {
        throw new ProductException(
          E::ts('An Error occurred while searching for the associated PriceField: ' . $e->getMessage()),
          ProductException::ERROR_CODE_PRICE_FIELD_NOT_FOUND);
      }
      throw new ProductException(
        E::ts('Could not delete associated PriceField: ' . $e->getMessage()),
        ProductException::ERROR_CODE_COULD_NOT_DELETE_PRICE_FIELD);
    }
    $this->price_field_id = NULL;
  }
}
