<?php

declare(strict_types = 1);

/**
 * Tests for detecting outdated Twingle products.
 *
 * @group headless
 * @covers \CRM_Twingle_BAO_TwingleProduct::checkOutdated
 * @covers \CRM_Twingle_BAO_TwingleProduct::transformTwingleAttrs
 * @covers \CRM_Twingle_BAO_TwingleProduct::add
 * @covers \CRM_Twingle_BAO_TwingleProduct::createPriceField
 * @covers \CRM_Twingle_BAO_TwingleProduct::load
 */
class CRM_Twingle_ProductOutdatedTest extends CRM_Twingle_TwingleProductTestCase {

  public function testProductWithVatIsUpToDate(): void {
    $product = $this->createProduct($this->mockProductFromTwingle(3.0, 0.07));
    self::assertSame(2.803738318, $product->price);
    self::assertSame(7.0, $product->vat_rate);

    $product->checkOutdated($this->mockProductFromTwingle(3.0, 0.07));
    self::assertEmpty(get_object_vars($product)['is_outdated'] ?? NULL);
  }

  public function testProductWithoutVatIsUpToDate(): void {
    $product = $this->createProduct($this->mockProductFromTwingle(3.0, 0));
    self::assertSame(3.0, $product->price);
    self::assertSame(0.0, $product->vat_rate);

    $product->checkOutdated($this->mockProductFromTwingle(3.0, 0));
    self::assertEmpty(get_object_vars($product)['is_outdated'] ?? NULL);
  }

  /**
   * Twingle does not provide the VAT rate for accounts which do not use its
   * VAT feature yet. These products have no VAT.
   */
  public function testProductWithoutVatFromTwingleIsUpToDate(): void {
    $product = $this->createProduct($this->mockProductFromTwingle(3.0, NULL));
    self::assertSame(3.0, $product->price);
    self::assertSame(0.0, $product->vat_rate);

    $product->checkOutdated($this->mockProductFromTwingle(3.0, NULL));
    self::assertEmpty(get_object_vars($product)['is_outdated'] ?? NULL);
  }

  public function testProductWithChangedPriceFieldAmountIsOutdated(): void {
    $product = $this->createProduct($this->mockProductFromTwingle(3.0, 0.07));
    \Civi\Api4\PriceFieldValue::update(FALSE)
      ->addWhere('price_field_id', '=', get_object_vars($product)['price_field_id'])
      ->addValue('amount', 2.5)
      ->execute();
    $product = $this->findProduct();
    self::assertSame(2.5, $product->price);

    $product->checkOutdated($this->mockProductFromTwingle(3.0, 0.07));
    self::assertTrue(get_object_vars($product)['is_outdated'] ?? NULL);
    self::assertSame(2.803738318, $product->price);
  }

  /**
   * Products created before the VAT rate was stored have no VAT rate and
   * their price fields contain the price including VAT. Updating them stores
   * the net price.
   */
  public function testUpdateOfProductWithoutStoredVatRateStoresNetPrice(): void {
    $product = $this->createProductWithoutVatRate(3.0);
    self::assertSame([self::SHOP_NAME => 1], CRM_Twingle_BAO_TwingleProduct::countProductsWithoutVatRate());

    $product->checkOutdated($this->mockProductFromTwingle(3.0, 0.07));
    self::assertTrue(get_object_vars($product)['is_outdated'] ?? NULL);
    $this->updateProduct($product);

    self::assertSame([], CRM_Twingle_BAO_TwingleProduct::countProductsWithoutVatRate());
    $product = $this->findProduct();
    self::assertSame(7.0, $product->vat_rate);
    self::assertSame(2.803738318, $product->price);
    $product->checkOutdated($this->mockProductFromTwingle(3.0, 0.07));
    self::assertEmpty(get_object_vars($product)['is_outdated'] ?? NULL);
  }

  /**
   * Products created before the VAT rate was stored get a VAT rate of 0 % if
   * Twingle does not provide the VAT rate. Their price does not change.
   */
  public function testUpdateOfProductWithoutStoredVatRateAndWithoutVatFromTwingleStoresVatRateZero(): void {
    $product = $this->createProductWithoutVatRate(3.0);
    self::assertSame([self::SHOP_NAME => 1], CRM_Twingle_BAO_TwingleProduct::countProductsWithoutVatRate());

    $product->checkOutdated($this->mockProductFromTwingle(3.0, NULL));
    self::assertTrue(get_object_vars($product)['is_outdated'] ?? NULL);
    $this->updateProduct($product);

    self::assertSame([], CRM_Twingle_BAO_TwingleProduct::countProductsWithoutVatRate());
    $product = $this->findProduct();
    self::assertSame(0.0, $product->vat_rate);
    self::assertSame(3.0, $product->price);
    $product->checkOutdated($this->mockProductFromTwingle(3.0, NULL));
    self::assertEmpty(get_object_vars($product)['is_outdated'] ?? NULL);
  }

  /**
   * A product whose price has been fixed in Twingle after its price field
   * has been created with a custom price.
   */
  public function testUpdateFixesPriceOfFormerCustomPriceProduct(): void {
    $product = $this->createProduct($this->mockProductFromTwingle(NULL, 0.07));
    self::assertNull($product->price);

    $product->checkOutdated($this->mockProductFromTwingle(3.0, 0.07));
    self::assertTrue(get_object_vars($product)['is_outdated'] ?? NULL);
    $result = $this->updateProduct($product);
    self::assertEquals(2.803738318, $result['price']);

    $price_field = \Civi\Api4\PriceField::get(FALSE)
      ->addSelect('is_enter_qty', 'is_display_amounts')
      ->addWhere('id', '=', get_object_vars($product)['price_field_id'])
      ->execute()
      ->single();
    self::assertFalse($price_field['is_enter_qty']);
    self::assertTrue($price_field['is_display_amounts']);

    $product = $this->findProduct();
    self::assertSame(2.803738318, $product->price);
    $product->checkOutdated($this->mockProductFromTwingle(3.0, 0.07));
    self::assertEmpty(get_object_vars($product)['is_outdated'] ?? NULL);
  }

}
