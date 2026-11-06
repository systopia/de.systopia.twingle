<?php

declare(strict_types = 1);

namespace Civi\Twingle\Shop\Utils;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Civi\Twingle\Shop\Utils\TwingleShopUtils
 */
class TwingleShopUtilsTest extends TestCase {

  /**
   * @dataProvider provideTaxCalculations
   */
  public function testTaxCalculation(float $gross_amount, float $tax_rate, float $tax_amount, float $net_amount): void {
    self::assertSame($tax_amount, TwingleShopUtils::calculate_included_tax($gross_amount, $tax_rate));
    self::assertSame($net_amount, TwingleShopUtils::calculate_net_amount($gross_amount, $tax_rate));
  }

  /**
   * @phpstan-return array<string, array{float, float, float, float}>
   */
  public function provideTaxCalculations(): array {
    return [
      'reduced rate' => [10.0, 7.0, 0.65, 9.35],
      'regular rate' => [10.0, 19.0, 1.6, 8.4],
      'no tax' => [10.0, 0.0, 0.0, 10.0],
      'zero price' => [0.0, 19.0, 0.0, 0.0],
    ];
  }

  /**
   * @dataProvider providePriceCalculations
   */
  public function testPriceCalculation(float $gross_price, float $tax_rate, float $net_price): void {
    self::assertSame($net_price, TwingleShopUtils::calculate_net_price($gross_price, $tax_rate));
    self::assertSame($gross_price, TwingleShopUtils::calculate_gross_price($net_price, $tax_rate));
  }

  /**
   * @phpstan-return array<string, array{float, float, float}>
   */
  public function providePriceCalculations(): array {
    return [
      'reduced rate' => [3.0, 7.0, 2.803738318],
      'regular rate' => [3.0, 19.0, 2.521008403],
      'no tax' => [3.0, 0.0, 3.0],
      'zero price' => [0.0, 19.0, 0.0],
    ];
  }

  /**
   * CiviCRM calculates the tax on the line total, i.e. on the net price
   * multiplied by the quantity. With the unrounded net price, this adds up to
   * the gross amount from Twingle.
   */
  public function testNetPriceAddsUpToGrossLineTotal(): void {
    $net_price = TwingleShopUtils::calculate_net_price(3.0, 7.0);
    for ($qty = 1; $qty <= 100; $qty++) {
      $net_line_total = $net_price * $qty;
      self::assertSame(
        round(3.0 * $qty, 2),
        round($net_line_total + $net_line_total * 0.07, 2),
        "Quantity $qty"
      );
    }
  }

}
