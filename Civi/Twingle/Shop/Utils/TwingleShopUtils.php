<?php

declare(strict_types = 1);

namespace Civi\Twingle\Shop\Utils;

class TwingleShopUtils {

  /**
   * Number of decimals of a price field amount
   * (civicrm_price_field_value.amount is decimal(18,9)).
   */
  public const PRICE_PRECISION = 9;

  /**
   * Filter for allowed attributes.
   *
   * @param array $data
   *  Data to filter.
   * @param array $allowed_attributes
   *  Allowed attributes.
   * @param array $can_be_zero
   *  Attributes that can be zero.
   *
   * @return void
   */
  public static function filter_attributes(array &$data, array $allowed_attributes, array $can_be_zero = []): void {
    // Remove empty values if not of type int
    $data = array_filter(
      $data,
      function($value, $key) use ($can_be_zero) {
        return !empty($value) || in_array($key, $can_be_zero, TRUE);
      },
      ARRAY_FILTER_USE_BOTH
    );

    // Filter for allowed attributes
    $data = array_intersect_key(
      $data,
      $allowed_attributes
    );
  }

  /**
   * Convert string values to int.
   *
   * @param array $data
   * @param array $str_to_int_conversion
   *
   * @return void
   */
  public static function convert_str_to_int(array &$data, array $str_to_int_conversion): void {
    // Convert string to int
    foreach ($str_to_int_conversion as $attribute) {
      if (isset($data[$attribute]) && $data[$attribute] !== '') {
        $data[$attribute] = (int) $data[$attribute];
      }
    }
  }

  /**
   * Convert string values to float.
   *
   * @param array<string, mixed> $data
   * @param list<string> $str_to_float_conversion
   *
   * @return void
   */
  public static function convert_str_to_float(array &$data, array $str_to_float_conversion): void {
    foreach ($str_to_float_conversion as $attribute) {
      if (isset($data[$attribute]) && $data[$attribute] !== '') {
        if (!is_numeric($data[$attribute])) {
          throw new \RuntimeException(
            "Could not convert attribute '$attribute' to float."
          );
        }
        $data[$attribute] = (float) $data[$attribute];
      }
    }
  }

  /**
   * Calculate the tax amount included in a gross amount.
   *
   * Uses the same calculation as CiviCRM core does for tax inclusive line
   * totals (see CRM_Price_BAO_LineItem::getTaxAmountForLineItem()).
   *
   * @param float $gross_amount
   *  Amount including tax.
   * @param float $tax_rate
   *  Tax rate in percent.
   *
   * @return float
   */
  public static function calculate_included_tax(float $gross_amount, float $tax_rate): float {
    return round($gross_amount - $gross_amount / (1 + $tax_rate / 100), 2);
  }

  /**
   * Calculate the net amount of a gross amount.
   *
   * @param float $gross_amount
   *  Amount including tax.
   * @param float $tax_rate
   *  Tax rate in percent.
   *
   * @return float
   */
  public static function calculate_net_amount(float $gross_amount, float $tax_rate): float {
    return round($gross_amount - self::calculate_included_tax($gross_amount, $tax_rate), 2);
  }

  /**
   * Calculate the unrounded net price of a gross price.
   *
   * CiviCRM adds the sales tax on top of the price field amount. The net price
   * must not be rounded to cents, otherwise the gross amount calculated by
   * CiviCRM may differ from the original price (e.g. 2 x 2.80 EUR + 7 % VAT =
   * 5.99 EUR instead of 6.00 EUR).
   *
   * @param float $gross_price
   *  Price including tax.
   * @param float $tax_rate
   *  Tax rate in percent.
   *
   * @return float
   *  The net price with the precision of a price field amount.
   */
  public static function calculate_net_price(float $gross_price, float $tax_rate): float {
    return round($gross_price / (1 + $tax_rate / 100), self::PRICE_PRECISION);
  }

  /**
   * Calculate the gross price of a net price, rounded to cents.
   *
   * @param float $net_price
   *  Price without tax.
   * @param float $tax_rate
   *  Tax rate in percent.
   *
   * @return float
   */
  public static function calculate_gross_price(float $net_price, float $tax_rate): float {
    return round($net_price * (1 + $tax_rate / 100), 2);
  }

  /**
   * Convert int values to bool.
   *
   * @param array $data
   * @param array $int_to_bool_conversion
   *
   * @return void
   */
  public static function convert_int_to_bool(array &$data, array $int_to_bool_conversion): void {
    // Convert int to bool
    foreach ($int_to_bool_conversion as $attribute) {
      if (isset($data[$attribute])) {
        $data[$attribute] = (bool) $data[$attribute];
      }
    }
  }

  /**
   * Convert string values to date.
   *
   * @param array $data
   * @param array $str_to_date_conversion
   *
   * @return void
   */
  public static function convert_str_to_date(array &$data, array $str_to_date_conversion): void {
    // Convert string to date
    foreach ($str_to_date_conversion as $attribute) {
      if (isset($data[$attribute]) && is_string($data[$attribute])) {
        $data[$attribute] = strtotime($data[$attribute]);
      }
    }
  }

  /**
   * Convert an empty string to null.
   *
   * @param array $data
   * @param array $empty_string_to_null
   *
   * @return void
   */
  public static function convert_empty_string_to_null(array &$data, array $empty_string_to_null): void {
    foreach ($empty_string_to_null as $attribute) {
      if (isset($data[$attribute]) && $data[$attribute] === '') {
        $data[$attribute] = NULL;
      }
    }
  }

  /**
   * Validate data types. Throws an exception if data type is not valid.
   *
   * @param array $data
   * @param array $allowed_attributes
   *
   * @return void
   */
  public static function validate_data_types(array &$data, array $allowed_attributes): void {
    foreach ($data as $key => $value) {
      // Skip empty values
      if (empty($value)) {
        continue;
      }

      // Find expected data type
      // It could be so easy...
      $expected_data_type = strtolower(\CRM_Utils_Type::typeToString($allowed_attributes[$key]));

      // Validate data type
      if (!\CRM_Utils_Type::validatePhpType($value, $expected_data_type)) {
        $given_type = gettype($value);
        throw new \RuntimeException(
          "Data type of attribute '$key' is $given_type, but $expected_data_type was expected."
        );
      }
    }
  }

}
