<?php

declare(strict_types = 1);

use Civi\Twingle\AbstractTwingleProductTestCase;

/**
 * Tests for the system check about products without VAT rate.
 *
 * @group headless
 * @covers ::twingle_civicrm_check
 * @covers \CRM_Twingle_BAO_TwingleProduct::countProductsWithoutVatRate
 */
class CRM_Twingle_ProductVatRateCheckTest extends AbstractTwingleProductTestCase {

  public function testWarnsAboutProductsWithoutVatRate(): void {
    Civi::settings()->set('twingle_use_shop', TRUE);
    $product = $this->createProductWithoutVatRate();

    $messages = $this->runCheck();
    self::assertCount(1, $messages);
    self::assertSame('twingle_product_vat_rate', $messages[0]->getName());
    self::assertStringContainsString(self::SHOP_NAME . ': 1', $messages[0]->getMessage());

    // Products with a VAT rate (including 0 %) are fine.
    $this->saveProduct(['vat_rate' => 0.0] + get_object_vars($product));
    self::assertSame([], $this->runCheck());
  }

  public function testNoWarningIfShopIntegrationIsDisabled(): void {
    Civi::settings()->set('twingle_use_shop', FALSE);
    $this->createProductWithoutVatRate();

    self::assertSame([], $this->runCheck());
  }

  /**
   * @return array<\CRM_Utils_Check_Message>
   */
  private function runCheck(): array {
    $messages = [];
    twingle_civicrm_check($messages, ['twingle_product_vat_rate']);
    return $messages;
  }

}
