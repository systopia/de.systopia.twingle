<?php

use CRM_Twingle_ExtensionUtil as E;
use CRM_Twingle_Exceptions_Shop_ProductException as TwingleShopProductException;

/**
 * This class represents a Twingle Shop product.
 */
class CRM_Twingle_Shop_TwingleProduct {

  /**
   * Allowed product attributes for filtering.
   */
  public const ALLOWED_PRODUCT_ATTRIBUTES = [
    "id" => CRM_Utils_Type::T_INT,
    "name" => CRM_Utils_Type::T_STRING,
    "is_active" => CRM_Utils_Type::T_BOOLEAN,
    "description" => CRM_Utils_Type::T_STRING,
    "text" => CRM_Utils_Type::T_STRING,
    "price" => CRM_Utils_Type::T_INT,
    "created_at" => CRM_Utils_Type::T_STRING,
    "updated_at"=> CRM_Utils_Type::T_STRING,
    "project_id" => CRM_Utils_Type::T_INT,
    # "images" => [],
    # "categories" = [],
    "sort" => CRM_Utils_Type::T_INT,
    "internal_id" => CRM_Utils_Type::T_STRING,
    "has_zero_price" => CRM_Utils_Type::T_BOOLEAN,
    # "name_plural" => CRM_Utils_Type::T_STRING,
    # "max_count" => ???,
    # "has_textinput" => CRM_Utils_Type::T_BOOLEAN,
    # "count" => CRM_Utils_Type::T_INT,
  ];

  /**
   * CRM_Twingle_Shop_Product constructor.
   * Please use load($data) method to load data from Twingle API.
   */
  public function __construct() {
    // Initialize allowed attributes
    foreach (self::ALLOWED_PRODUCT_ATTRIBUTES as $key => $_) {
      $this->$key = NULL;
    }
  }

  /**
   * Load product data.
   *
   * @param array $productData
   *  Product data array from Twingle API
   *
   * @return void
   *
   * @throws TwingleShopProductException
   */
  public function load(array $productData): void {

    // Filter for allowed attributes
    $attributes = array_intersect_key(
      $productData,
      self::ALLOWED_PRODUCT_ATTRIBUTES
    );

    foreach ($attributes as $key => $value) {

      // Skip empty values
      if (empty($value)) {
        continue;
      }

      // Find expected data type
      $expectedDataType = strtolower(CRM_Utils_Type::typeToString(self::ALLOWED_PRODUCT_ATTRIBUTES[$key])); // It could be so easy...

      // Validate data type
      if (!CRM_Utils_Type::validatePhpType($value, $expectedDataType)) {
        throw new TwingleShopProductException(
          E::ts("Data type of attribute '%3' is %2, but %1 was expected.",
            [
              1 => $expectedDataType,
              2 => gettype($value),
              3 => $key,
            ]),
          TwingleShopProductException::ERROR_CODE_ATTRIBUTE_WRONG_DATA_TYPE
        );
      }

      // Set attributes
      else {
        $this->$key = $value;
      }
    }
  }

  /**
   * Creates a price field to represents this product in CiviCRM.
   *
   * @param int $priceSetId
   *  ID of the price set for this price field
   *
   * @return int
   *  Price field ID
   */
  public function createPriceField(int $priceSetId): int {
    // TODO
    return 1;
  }

  /**
   * Synchronize price field with product.
   *
   * @return void
   */
  public function syncPriceField(): void {
    // TODO
  }
}
