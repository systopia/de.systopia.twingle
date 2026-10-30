<?php
/*------------------------------------------------------------+
| SYSTOPIA Twingle Integration                                |
| Copyright (C) 2025 SYSTOPIA                                 |
+-------------------------------------------------------------+
| This program is released as free software under the         |
| Affero GPL license. You can redistribute it and/or          |
| modify it under the terms of this license which you         |
| can read by viewing the included agpl.txt or online         |
| at www.gnu.org/licenses/agpl.html. Removal of this          |
| copyright header is strictly prohibited without             |
| written permission from the original author(s).             |
+-------------------------------------------------------------*/

declare(strict_types = 1);

use Civi\Twingle\AbstractTwingleHeadlessTestCase;

/**
 * Tests for CRM_Twingle_Submission.
 *
 * @group headless
 * @covers \CRM_Twingle_Submission
 */
class CRM_Twingle_SubmissionTest extends AbstractTwingleHeadlessTestCase {

  use \Civi\Test\Api3TestTrait;

  /**
   * Without configured cycle days CiviSEPA allows every day from 1 to 28: the
   * earliest collection day itself, or the 1st of the next month after the 28th.
   */
  public function testSEPACycleDayWithoutConfiguredCycleDays(): void {
    CRM_Sepa_Logic_Settings::setSetting(0, 'pp_buffer_days');
    CRM_Sepa_Logic_Settings::setSetting(0, 'batching.FRST.notice');

    self::assertSame(3, CRM_Twingle_Submission::getSEPACycleDay('20260603120000', 1));
    self::assertSame(28, CRM_Twingle_Submission::getSEPACycleDay('20260628120000', 1));
    self::assertSame(1, CRM_Twingle_Submission::getSEPACycleDay('20260629120000', 1));
  }

  /**
   * Line items for products without a mapped TwingleProduct (and the donation
   * line item) must reference the default contribution price field, otherwise
   * core's Order BAO crashes with setPriceSetID(NULL) on any later update of
   * the contribution.
   */
  public function testCreateLineItemsFallsBackToDefaultPriceField(): void {
    /** @phpstan-var array{id: int|string} $contact */
    $contact = $this->callAPISuccess('Contact', 'create', [
      'contact_type' => 'Individual',
      'first_name' => 'Twingle',
      'last_name' => 'Test',
    ]);
    /** @phpstan-var array{id: int|string} $contribution */
    $contribution = $this->callAPISuccess('Contribution', 'create', [
      'contact_id' => $contact['id'],
      'financial_type_id' => 1,
      'total_amount' => 25.0,
    ]);

    $values = [
      'contribution' => [
        'id' => $contribution['id'],
        'total_amount' => 25.0,
      ],
    ];
    // A product ID without a corresponding TwingleProduct record, so
    // createLineItems() takes the fallback branch. The remaining 15.0 of the
    // contribution amount becomes the donation line item.
    $submission = [
      'products' => [
        [
          'id' => 999999,
          'name' => 'Unmapped product',
          'price' => 10.0,
          'count' => 1,
          'total_value' => 10.0,
        ],
      ],
    ];
    $profile = CRM_Twingle_Profile::createDefaultProfile();

    $line_items = CRM_Twingle_Submission::createLineItems($values, $submission, $profile);

    $default_price_set = CRM_Price_BAO_PriceSet::getDefaultPriceSet('contribution');
    /** @phpstan-var array{priceFieldID: int|string, priceFieldValueID: int|string} $default_price_field */
    $default_price_field = reset($default_price_set);

    self::assertCount(2, $line_items);
    foreach ($line_items as $line_item) {
      self::assertEquals($default_price_field['priceFieldID'], $line_item['price_field_id']);
      self::assertEquals($default_price_field['priceFieldValueID'], $line_item['price_field_value_id']);
    }
  }

}
