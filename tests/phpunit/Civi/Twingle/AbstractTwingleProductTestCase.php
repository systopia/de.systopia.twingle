<?php

declare(strict_types = 1);

namespace Civi\Twingle;

use PHPUnit\Framework\TestCase;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * Base class for tests with Twingle products of a Twingle shop.
 *
 * Shop and products are created through the API like the profile form does.
 */
abstract class AbstractTwingleProductTestCase extends TestCase implements HeadlessInterface, TransactionalInterface {

  protected const EXTERNAL_ID = 2534;

  protected const PROJECT_ID = 123;

  protected const TW_UPDATED_AT = 1700000000;

  protected const SHOP_NAME = 'Coffee Shop';

  protected int $shopId;

  /**
   * {@inheritDoc}
   */
  public function setUpHeadless(): CiviEnvBuilder {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  protected function setUp(): void {
    parent::setUp();
    // Creating the price fields requires permissions
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['all CiviCRM permissions and ACLs'];

    /** @phpstan-var array{id: int|string} $result */
    $result = \civicrm_api3('TwingleShop', 'create', [
      'project_identifier' => 'tw123',
      'numerical_project_id' => self::PROJECT_ID,
      'name' => self::SHOP_NAME,
      'financial_type_id' => $this->getFinancialTypeId(),
    ]);
    $this->shopId = (int) $result['id'];
  }

  /**
   * @param float|null $price
   *   NULL for products with a custom price.
   * @param float|int|null $included_tax
   *   NULL for products without the attribute `included_tax`.
   *
   * @phpstan-return array<string, mixed>
   */
  protected function mockProductFromTwingle(?float $price = 3.0, float|int|null $included_tax = NULL): array {
    $product = [
      'id' => self::EXTERNAL_ID,
      'name' => 'Coffee',
      'price' => $price,
      'updated_at' => self::TW_UPDATED_AT,
      'created_at' => self::TW_UPDATED_AT,
      'project_id' => self::PROJECT_ID,
    ];
    if ($included_tax !== NULL) {
      $product['included_tax'] = $included_tax;
    }
    return $product;
  }

  /**
   * Create a product from Twingle like the profile form does.
   *
   * @phpstan-param array<string, mixed> $product_from_twingle
   */
  protected function createProduct(array $product_from_twingle): \CRM_Twingle_BAO_TwingleProduct {
    $this->saveProduct(\CRM_Twingle_BAO_TwingleProduct::transformTwingleAttrs($product_from_twingle));
    return $this->findProduct();
  }

  /**
   * Create a product without VAT rate.
   */
  protected function createProductWithoutVatRate(float $price = 3.0): \CRM_Twingle_BAO_TwingleProduct {
    $values = \CRM_Twingle_BAO_TwingleProduct::transformTwingleAttrs($this->mockProductFromTwingle($price));
    unset($values['vat_rate']);
    $this->saveProduct($values);
    $product = $this->findProduct();
    self::assertNull($product->vat_rate);
    return $product;
  }

  /**
   * Update the product like the profile form does.
   *
   * @phpstan-return array<string, mixed>
   *   The values of the updated product.
   */
  protected function updateProduct(\CRM_Twingle_BAO_TwingleProduct $product): array {
    return $this->saveProduct(get_object_vars($product));
  }

  /**
   * @phpstan-param array<string, mixed> $values
   *
   * @phpstan-return array<string, mixed>
   *   The values of the saved product.
   */
  protected function saveProduct(array $values): array {
    $values += [
      'financial_type_id' => $this->getFinancialTypeId(),
      'twingle_shop_id' => $this->shopId,
    ];
    /** @phpstan-var array{values: array<string, mixed>} $result */
    $result = \civicrm_api3('TwingleProduct', 'create', array_intersect_key($values, array_flip([
      'id',
      'name',
      'is_active',
      'price',
      'vat_rate',
      'external_id',
      'project_id',
      'financial_type_id',
      'price_field_id',
      'tw_updated_at',
      'twingle_shop_id',
    ])));
    return $result['values'];
  }

  protected function findProduct(): \CRM_Twingle_BAO_TwingleProduct {
    $product = \CRM_Twingle_BAO_TwingleProduct::findByExternalId(self::EXTERNAL_ID);
    self::assertNotNull($product);
    return $product;
  }

  protected function getFinancialTypeId(): int {
    return (int) \CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Donation');
  }

}
