<?php

namespace Civi\Twingle\Shop\Utils;

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
function filter_attributes(array &$data, array $allowed_attributes, array $can_be_zero = Null): void {
  $can_be_zero = $can_be_zero ?? [];

  // Remove empty values if not of type int
  $data = array_filter(
    $data,
    function($value, $key) use ($can_be_zero) {
      return !empty($value) || in_array($key, $can_be_zero);
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
 * @throws \Exception
 */
function convert_str_to_int(array &$data, array $str_to_int_conversion): void {
  // Convert string to int
  foreach ($str_to_int_conversion as $attribute) {
    if (isset($data[$attribute])) {
      try {
        $data[$attribute] = (int) $data[$attribute];
      } catch (\Exception $e) {
        throw new \Exception(
          "Could not convert attribute '$attribute' to int."
        );
      }
    }
  }
}

/**
 * Convert int values to bool.
 *
 * @param array $data
 * @param array $int_to_bool_conversion
 *
 * @return void
 * @throws \Exception
 */
function convert_int_to_bool(array &$data, array $int_to_bool_conversion): void {
  // Convert int to bool
  foreach ($int_to_bool_conversion as $attribute) {
    if (isset($data[$attribute])) {
      try {
        $data[$attribute] = (bool) $data[$attribute];
      }
      catch (\Exception $e) {
        throw new \Exception(
          "Could not convert attribute '$attribute' to bool."
        );
      }
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
 * @throws \Exception
 */
function convert_str_to_date(array &$data, array $str_to_date_conversion): void {
  // Convert string to date
  foreach ($str_to_date_conversion as $attribute) {
    if (isset($data[$attribute]) && is_string($data[$attribute])) {
      try {
        $data[$attribute] = strtotime($data[$attribute]);
      }
      catch (\Exception $e) {
        throw new \Exception(
          "Could not convert attribute '$attribute' to date."
        );
      }
    }
  }
}

/**
 * Convert null to int
 *
 * @param array $data
 * @param array $null_to_int_conversion
 *
 * @return void
 */
function convert_null_to_int(array &$data, array $null_to_int_conversion): void {
  // Convert null to int
  foreach ($null_to_int_conversion as $attribute) {
    if (array_key_exists($attribute, $data) && $data[$attribute] === NULL) {
      $data[$attribute] = 0;
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
 * @throws \Exception
 */
function validate_data_types(array &$data, array $allowed_attributes): void {
  foreach ($data as $key => $value) {
    // Skip empty values
    if (empty($value)) {
      continue;
    }

    // Find expected data type
    $expected_data_type = strtolower(\CRM_Utils_Type::typeToString($allowed_attributes[$key])); // It could be so easy...

    // Validate data type
    if (!\CRM_Utils_Type::validatePhpType($value, $expected_data_type)) {
      $given_type = gettype($value);
      throw new \Exception(
        "Data type of attribute '$key' is $given_type, but $expected_data_type was expected."
      );
    }
  }
}

