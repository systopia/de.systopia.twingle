<?php

declare(strict_types = 1);

use Civi\Api4\PriceFieldValue;
use Civi\Twingle\Shop\Exceptions\ProductException;
use Civi\Twingle\Shop\Exceptions\ShopException;
use CRM_Twingle_ExtensionUtil as E;
use Civi\Twingle\Shop\Utils\TwingleShopUtils;

/**
 * TwingleProduct BAO class.
 * This class is used to implement the logic for the TwingleProduct entity.
 */
class CRM_Twingle_BAO_TwingleProduct extends CRM_Twingle_DAO_TwingleProduct {

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
   * Rate of the VAT.
   *
   * @var float|null
   */
  public $vat_rate;

  /**
   * The values of this attributes can be 0.
   * (For filtering purposes)
   */
  protected const CAN_BE_ZERO = [
    'price',
    'sort',
    'vat_rate',
  ];

  /**
   * Attributes that need to be converted to int.
   */
  protected const STR_TO_INT_CONVERSION = [
    'id',
    'twingle_shop_id',
    'financial_type_id',
    'price_field_id',
    'project_id',
    'external_id',
    'tw_updated_at',
    'tw_created_at',
    'sort',
  ];

  /**
   * Attributes that need to be converted to float.
   */
  protected const STR_TO_FLOAT_CONVERSION = [
    'price',
    'vat_rate',
  ];

  /**
   * Attributes that need to be converted to boolean.
   */
  protected const INT_TO_BOOL_CONVERSION = [
    'is_active',
  ];

  /**
   * String to date conversion.
   */
  protected const STR_TO_DATE_CONVERSION = [
    'created_at',
    'updated_at',
  ];

  /**
   * Empty string to null conversion.
   */
  protected const EMPTY_STRING_TO_NULL = [
    'price',
    'vat_rate',
  ];

  /**
   * Allowed product attributes.
   * Attributes that we currently don't support are commented out.
   */
  protected const ALLOWED_ATTRIBUTES = [
    'id' => CRM_Utils_Type::T_INT,
    'external_id' => CRM_Utils_Type::T_INT,
    'name' => CRM_Utils_Type::T_STRING,
    'is_active' => CRM_Utils_Type::T_BOOLEAN,
    'description' => CRM_Utils_Type::T_STRING,
    'price' => CRM_Utils_Type::T_FLOAT,
    'created_at' => CRM_Utils_Type::T_INT,
    'tw_created_at' => CRM_Utils_Type::T_INT,
    'updated_at' => CRM_Utils_Type::T_INT,
    'tw_updated_at' => CRM_Utils_Type::T_INT,
    'is_orphaned' => CRM_Utils_Type::T_BOOLEAN,
    'is_outdated' => CRM_Utils_Type::T_BOOLEAN,
    'project_id' => CRM_Utils_Type::T_INT,
    'sort' => CRM_Utils_Type::T_INT,
    'financial_type_id' => CRM_Utils_Type::T_INT,
    'twingle_shop_id' => CRM_Utils_Type::T_INT,
    'price_field_id' => CRM_Utils_Type::T_INT,
    'vat_rate' => CRM_Utils_Type::T_FLOAT,
    // phpcs:disable Squiz.PHP.CommentedOutCode.Found
    # "text" => \CRM_Utils_Type::T_STRING,
    # "images" => \CRM_Utils_Type::T_STRING,
    # "categories" = \CRM_Utils_Type::T_STRING,
    # "internal_id" => \CRM_Utils_Type::T_STRING,
    # "has_zero_price" => \CRM_Utils_Type::T_BOOLEAN,
    # "name_plural" => \CRM_Utils_Type::T_STRING,
    # "max_count" => \CRM_Utils_Type::T_INT,
    # "has_textinput" => \CRM_Utils_Type::T_BOOLEAN,
    # "count" => \CRM_Utils_Type::T_INT,
    // phpcs:enable
  ];

  /**
   * Transform attribute names and values to match the database column names
   * and types.
   *
   * @param array<string, mixed> $values
   *   Array with product data from Twingle API
   *
   * @return array<string, mixed>
   */
  public static function transformTwingleAttrs(array $values): array {
    $new_values = [];

    foreach ($values as $key => $value) {
      switch ($key) {
        case 'id':
          $key = 'external_id';
          break;

        case 'updated_at':
          $key = 'tw_updated_at';
          break;

        case 'created_at':
          $key = 'tw_created_at';
          break;

        case 'included_tax':
          $key = 'vat_rate';
          $value = is_numeric($value) ? round((float) $value * 100, 2) : 0.0;
          break;
      }

      $new_values[$key] = $value;
    }

    // Twingle does not provide the VAT rate for accounts which do not use its
    // VAT feature yet. These products have no VAT.
    if (!array_key_exists('included_tax', $values)) {
      $new_values['vat_rate'] = 0.0;
    }

    // Twingle prices include VAT, but CiviCRM adds the sales tax of the
    // financial type on top of the price. So we store the unrounded net price.
    if (
      is_numeric($new_values['price'] ?? NULL)
      && is_float($new_values['vat_rate'] ?? NULL)
      && $new_values['vat_rate'] > 0
    ) {
      $new_values['price'] = TwingleShopUtils::calculate_net_price(
        (float) $new_values['price'],
        $new_values['vat_rate']
      );
    }

    return $new_values;
  }

  /**
   * Load product data.
   *
   * @param array $product_data
   *   Array with product data
   *
   * @return void
   *
   * @throws ProductException
   * @throws \Exception
   */
  // phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh, Drupal.WhiteSpace.ScopeIndent.IncorrectExact
  public function load(array $product_data): void {
  // phpcs:enable
    // Filter for allowed attributes
    TwingleShopUtils::filter_attributes(
      $product_data,
      self::ALLOWED_ATTRIBUTES,
      self::CAN_BE_ZERO,
    );

    // Does this product allow to enter a custom price? Only fall back to the
    // existing price field if no price is given, otherwise a product whose
    // price has been fixed in Twingle could never be updated.
    $custom_price = array_key_exists('price', $product_data) && $product_data['price'] === NULL;
    if (!$custom_price && !isset($product_data['price']) && isset($product_data['price_field_id'])) {
      try {
        $price_field = civicrm_api3('PriceField', 'getsingle', [
          'id' => $product_data['price_field_id'],
          'return' => 'is_enter_qty',
        ]);
        $custom_price = (bool) $price_field['is_enter_qty'];
      }
      catch (CRM_Core_Exception $e) {
        throw new ProductException(
          E::ts("Could not find PriceField for Twingle Product ['id': %1, 'external_id': %2]: %3",
            [
              1 => $product_data['id'],
              2 => $product_data['external_id'],
              3 => $e->getMessage(),
            ]),
          ProductException::ERROR_CODE_PRICE_FIELD_NOT_FOUND, $e);
      }
    }

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
          ProductException::ERROR_CODE_PRICE_FIELD_VALUE_NOT_FOUND, $e);
      }
      $product_data['name'] = $product_data['name'] ?? $price_field_value['label'];
      $product_data['price'] = $custom_price ? NULL : $product_data['price'] ?? $price_field_value['amount'];
      $product_data['financial_type_id'] = $product_data['financial_type_id']
        ?? $price_field_value['financial_type_id'];
      $product_data['is_active'] = $product_data['is_active'] ?? $price_field_value['is_active'];
      $product_data['sort'] = $product_data['sort'] ?? $price_field_value['weight'];
      $product_data['description'] = $product_data['description'] ?? $price_field_value['description'] ?? NULL;
    }

    // Change data types
    try {
      TwingleShopUtils::convert_str_to_int($product_data, self::STR_TO_INT_CONVERSION);
      TwingleShopUtils::convert_str_to_float($product_data, self::STR_TO_FLOAT_CONVERSION);
      TwingleShopUtils::convert_int_to_bool($product_data, self::INT_TO_BOOL_CONVERSION);
      TwingleShopUtils::convert_str_to_date($product_data, self::STR_TO_DATE_CONVERSION);
      TwingleShopUtils::convert_empty_string_to_null($product_data, self::EMPTY_STRING_TO_NULL);
    }
    catch (\Exception $e) {
      throw new ProductException($e->getMessage(), ProductException::ERROR_CODE_ATTRIBUTE_WRONG_DATA_TYPE, $e);
    }

    // Validate data types
    try {
      TwingleShopUtils::validate_data_types($product_data, self::ALLOWED_ATTRIBUTES);
    }
    catch (\Exception $e) {
      throw new ProductException($e->getMessage(), ProductException::ERROR_CODE_ATTRIBUTE_WRONG_DATA_TYPE, $e);
    }

    // Set attributes
    foreach ($product_data as $key => $value) {
      $this->$key = $value;
    }
  }

  /**
   * Creates a price field to represents this product in CiviCRM.
   *
   * @throws \Civi\Twingle\Shop\Exceptions\ProductException
   */
  // phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh, Drupal.WhiteSpace.ScopeIndent.IncorrectExact
  public function createPriceField() {
  // phpcs: enable
    // Define mode for PriceField
    $mode = $this->price_field_id ? 'edit' : 'create';
    $action = $mode === 'create' ? 'create' : 'update';

    // Check if PriceSet for this Shop already exists
    try {
      $price_field = civicrm_api3('PriceField', 'get', [
        'name' => 'tw_product_' . $this->external_id,
      ]);
      if ($price_field['count'] > 0 && $mode === 'create') {
        throw new ProductException(
          E::ts('PriceField for this Twingle Product already exists.'),
          ProductException::ERROR_CODE_PRICE_FIELD_ALREADY_EXISTS
        );
      }
      elseif ($price_field['count'] === 0 && $mode === 'edit') {
        throw new ProductException(
          E::ts('PriceField for this Twingle Product does not exist and cannot be edited.'),
          ProductException::ERROR_CODE_PRICE_FIELD_NOT_FOUND
        );
      }
    }
    catch (CRM_Core_Exception $e) {
      throw new ProductException(
        E::ts('Could not check if PriceField for this Twingle Product already exists.'),
        ProductException::ERROR_CODE_PRICE_FIELD_NOT_FOUND,
        $e
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
        $e
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
      'is_required' => FALSE,
    ];

    // If the product has no fixed price, allow the user to enter a custom price.
    // Set both explicitly, as the price of an existing product may have been
    // fixed in the meantime.
    $price_field_data['is_enter_qty'] = $this->price === NULL;
    $price_field_data['is_display_amounts'] = $this->price !== NULL;

    // Add id if in edit mode
    if ($mode === 'edit') {
      $price_field_data['id'] = $this->price_field_id;
    }
    try {
      /** @phpstan-var array{id: int|string} $price_field */
      $price_field = civicrm_api4(
        'PriceField',
        $action,
        ['values' => $price_field_data],
      )->first();
      $this->price_field_id = (int) $price_field['id'];
    }
    catch (CRM_Core_Exception $e) {
      throw new ProductException(
        E::ts(
          'Could not create PriceField for this Twingle Product: %1',
          [1 => $e->getMessage()]
        ),
        ProductException::ERROR_CODE_COULD_NOT_CREATE_PRICE_FIELD,
        $e
      );
    }

    // Try to find existing PriceFieldValue if in edit mode
    $price_field_value = NULL;
    if ($mode === 'edit') {
      try {
        $price_field_value = civicrm_api3('PriceFieldValue', 'getsingle', [
          'price_field_id' => $this->price_field_id,
        ]);
      }
      catch (CRM_Core_Exception $e) {
        throw new ProductException(
          E::ts('Could not find PriceFieldValue for this Twingle Product: %1',
            [1 => $e->getMessage()]),
          ProductException::ERROR_CODE_PRICE_FIELD_VALUE_NOT_FOUND,
          $e
        );
      }
    }

    // Create PriceFieldValue
    $price_field_value_data = [
      'price_field_id' => $this->price_field_id,
      'financial_type_id' => $this->financial_type_id,
      'label' => $this->name,
      'amount' => $this->price === NULL ? 1 : $this->price,
      'is_active' => $this->is_active,
      'description' => $this->description,
    ];
    // Add id if in edit mode
    if ($mode === 'edit' && $price_field_value) {
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
        ProductException::ERROR_CODE_COULD_NOT_CREATE_PRICE_FIELD_VALUE,
        $e
      );
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
    // Add financial type id of this product if it exists
      )
      + ['financial_type_id' => $this->getFinancialTypeId()];
  }

  /**
   * Find TwingleProduct by its external ID.
   *
   * @param int $external_id
   *   External id of the product (by Twingle)
   *
   * @return CRM_Twingle_BAO_TwingleProduct|null
   *   TwingleProduct object or NULL if not found
   *
   * @throws \Civi\Twingle\Shop\Exceptions\ProductException
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public static function findByExternalId($external_id) {
    $dao = CRM_Twingle_BAO_TwingleShop::executeQuery('SELECT * FROM civicrm_twingle_product WHERE external_id = %1',
      [1 => [$external_id, 'String']]);
    if ($dao->fetch()) {
      $product = new self();
      $product->load($dao->toArray());
      return $product;
    }
    return NULL;
  }

  /**
   * Count the products with an active price field but without a VAT rate,
   * grouped by shop. These are products which have been created before the
   * VAT rate was stored and whose price fields still contain the gross price.
   *
   * @return array<string, int>
   *   Number of products by shop name
   *
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public static function countProductsWithoutVatRate(): array {
    // The check may run before the upgrade adding the column has been applied.
    if (!CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_twingle_product', 'vat_rate')) {
      return [];
    }

    $dao = CRM_Core_DAO::executeQuery(
      'SELECT ts.name, COUNT(tp.id) AS product_count
      FROM civicrm_twingle_product tp
      INNER JOIN civicrm_twingle_shop ts ON ts.id = tp.twingle_shop_id
      INNER JOIN civicrm_price_field pf ON pf.id = tp.price_field_id
      WHERE tp.vat_rate IS NULL AND pf.is_active = 1
      GROUP BY ts.id, ts.name
      ORDER BY ts.name'
    );
    if (!$dao instanceof CRM_Core_DAO) {
      return [];
    }
    $counts = [];
    foreach ($dao->fetchMap('name', 'product_count') as $shop_name => $count) {
      $counts[(string) $shop_name] = is_numeric($count) ? (int) $count : 0;
    }
    return $counts;
  }

  /**
   * Add Twingle Product
   *
   * @param string $mode
   *   'create' or 'edit'
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
      $dao = CRM_Twingle_BAO_TwingleShop::executeQuery('SELECT * FROM civicrm_twingle_product WHERE external_id = %1',
        [1 => [$this->external_id, 'String']]);
      if ($dao->fetch()) {
        $this->copyValues(array_merge($dao->toArray(), $this->getAttributes()));
      }
    }
    catch (\Civi\Core\Exception\DBQueryException $e) {
      throw new ProductException(
        E::ts('Could not find TwingleProduct in database: %1', [1 => $e->getMessage()]),
        ShopException::ERROR_CODE_COULD_NOT_FIND_SHOP_IN_DB,
        $e
      );
    }

    // Register pre-hook
    $twingle_product_values = $this->getAttributes();
    try {
      \CRM_Utils_Hook::pre($mode, 'TwingleProduct', $this->id, $twingle_product_values);
    }
    catch (\Exception $e) {
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
    }
    catch (\Exception $e) {
      $tx->rollback();
      throw new ProductException(
        E::ts('Could not save TwingleProduct to database: %1', [1 => $e->getMessage()]),
        ProductException::ERROR_CODE_COULD_NOT_CREATE_PRODUCT,
        $e
      );
    }
    // Reload the product along with the price from its price field, which is
    // not stored in the product table
    $result = self::findByExternalId($this->external_id);
    if ($result === NULL) {
      $tx->rollback();
      throw new ProductException(
        E::ts('Could not find TwingleProduct in database after saving it.'),
        ProductException::ERROR_CODE_COULD_NOT_CREATE_PRODUCT
      );
    }
    $this->load($result->getAttributes());

    // Register post-hook
    $twingle_product_values = $this->getAttributes();
    try {
      \CRM_Utils_Hook::post($mode, 'TwingleProduct', (int) $this->id, $twingle_product_values);
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
   * @override \CRM_Twingle_DAO_TwingleProduct::delete
   * @throws \CRM_Core_Exception
   * @throws \Civi\Twingle\Shop\Exceptions\ProductException
   */
  public function delete($useWhere = FALSE) {
    // Register post-hook
    $twingle_product_values = $this->getAttributes();
    \CRM_Utils_Hook::pre('delete', 'TwingleProduct', $this->id, $twingle_product_values);
    $this->load($twingle_product_values);

    // Delete TwingleProduct
    parent::delete($useWhere);

    // Register post-hook
    if (is_numeric($this->id)) {
      \CRM_Utils_Hook::post(
        'delete',
        'TwingleProduct',
        (int) $this->id,
        $instance
      );
    }

    // Free global arrays associated with this object
    $this->free();

    return TRUE;
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
    $values_from_twingle = self::transformTwingleAttrs($product_from_twingle);
    $vat_rate_from_twingle = is_float($values_from_twingle['vat_rate'] ?? NULL)
      ? $values_from_twingle['vat_rate']
      : NULL;

    // Mark outdated products which have a newer timestamp in Twingle or whose
    // VAT rate differs (e.g. products created before the VAT rate was stored)
    // or whose price differs (e.g. price fields changed in CiviCRM)
    if (
      intval($this->updated_at) < intval($product_from_twingle['updated_at'])
      || $this->vat_rate !== $vat_rate_from_twingle
      || self::roundPrice($this->price) !== self::roundPrice($values_from_twingle['price'] ?? NULL)
    ) {
      // Overwrite the product with the data from Twingle
      $this->load($values_from_twingle);
      $this->is_outdated = TRUE;
    }
  }

  /**
   * Round a price to the precision of a price field amount for comparison.
   *
   * @param mixed $price
   *
   * @return float|null
   *   NULL if the price is not numeric (i.e. custom price)
   */
  private static function roundPrice($price): ?float {
    return is_numeric($price) ? round((float) $price, TwingleShopUtils::PRICE_PRECISION) : NULL;
  }

  /**
   * Compare two products
   *
   * @param CRM_Twingle_BAO_TwingleProduct $product_to_compare_with
   *   Product from database
   *
   * @return bool
   */
  public function equals($product_to_compare_with) {
    return $this->name === $product_to_compare_with->name &&
      $this->description === $product_to_compare_with->description &&
      $this->text === $product_to_compare_with->text &&
      $this->price === $product_to_compare_with->price &&
      $this->external_id === $product_to_compare_with->external_id &&
      $this->vat_rate === $product_to_compare_with->vat_rate;
  }

  /**
   * Returns the financial type id of this product.
   *
   * @return int|null
   * @throws \CRM_Core_Exception
   */
  public function getFinancialTypeId(): ?int {
    if (!empty($this->price_field_id)) {
      // The financial type is stored in the price field value
      $price_field_value = PriceFieldValue::get()
        ->addSelect('financial_type_id')
        ->addWhere('price_field_id', '=', $this->price_field_id)
        ->execute()
        ->first();
      return $price_field_value['financial_type_id'] ?? NULL;
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
      /** @phpstan-var array{id: int} $price_field_value */
      $price_field_value = PriceFieldValue::get()
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
        E::ts('An Error occurred while searching for the associated PriceFieldValue: %1', [1 => $e->getMessage()]),
        ProductException::ERROR_CODE_PRICE_FIELD_VALUE_NOT_FOUND,
        $e
      );
    }
    try {
      civicrm_api3('PriceFieldValue', 'delete', ['id' => $result['id']]);
    }
    catch (CRM_Core_Exception $e) {
      throw new ProductException(
        E::ts('Could not delete associated PriceFieldValue: %1', [1 => $e->getMessage()]),
        ProductException::ERROR_CODE_COULD_NOT_DELETE_PRICE_FIELD_VALUE,
        $e
      );
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
            ProductException::ERROR_CODE_PRICE_FIELD_STILL_EXISTS,
            $e
          );
        }
      }
      catch (CRM_Core_Exception $e) {
        throw new ProductException(
          E::ts('An Error occurred while searching for the associated PriceField: %1', [1 => $e->getMessage()]),
          ProductException::ERROR_CODE_PRICE_FIELD_NOT_FOUND,
          $e
        );
      }
      throw new ProductException(
        E::ts('Could not delete associated PriceField: %1', [1 => $e->getMessage()]),
        ProductException::ERROR_CODE_COULD_NOT_DELETE_PRICE_FIELD
      );
    }
    $this->price_field_id = NULL;
  }

}
