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

  /**
   * Twingle submits amounts including VAT. If the financial type of a line
   * item has a sales tax account, the included tax must be split off, so that
   * core does not add the tax on top of the submitted amount.
   */
  public function testCreateLineItemsSplitsOffIncludedTax(): void {
    $financial_type_id = $this->createTaxedFinancialType(7.0);

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
      'skipLineItem' => 1,
    ]);

    $values = [
      'contribution' => [
        'id' => $contribution['id'],
        'total_amount' => 25.0,
      ],
    ];
    // Two products à 10.00 including 7 % VAT, the remaining 5.00 of the
    // contribution amount become the donation line item.
    $submission = [
      'products' => [
        [
          'id' => 999999,
          'name' => 'Coffee',
          'price' => 10.0,
          'count' => 2,
          'total_value' => 20.0,
        ],
      ],
    ];
    $profile = CRM_Twingle_Profile::createDefaultProfile();
    $profile->setAttribute('shop_financial_type', $financial_type_id);
    $profile->setAttribute('shop_donation_financial_type', 1);

    $line_items = CRM_Twingle_Submission::createLineItems($values, $submission, $profile);
    self::assertCount(2, $line_items);

    /** @phpstan-var array<string, mixed> $product_line_item */
    $product_line_item = $this->callAPISuccessGetSingle('LineItem', ['id' => $line_items[0]['id']]);
    self::assertEquals(9.35, $product_line_item['unit_price']);
    self::assertEquals(18.69, $product_line_item['line_total']);
    self::assertEquals(1.31, $product_line_item['tax_amount']);

    /** @phpstan-var array<string, mixed> $donation_line_item */
    $donation_line_item = $this->callAPISuccessGetSingle('LineItem', ['id' => $line_items[1]['id']]);
    self::assertEquals(5.0, $donation_line_item['line_total']);
    self::assertEquals(0.0, $donation_line_item['tax_amount']);

    /** @phpstan-var array{tax_amount: string, total_amount: string} $contribution */
    $contribution = \Civi\Api4\Contribution::get(FALSE)
      ->addSelect('tax_amount', 'total_amount')
      ->addWhere('id', '=', $contribution['id'])
      ->execute()
      ->single();
    self::assertEquals(1.31, (float) $contribution['tax_amount']);
    self::assertEquals(25.0, (float) $contribution['total_amount']);

    // Income and tax of the product and income of the donation must have been
    // booked and assigned to the payment.
    $financial_items = $this->getFinancialItems($line_items);
    self::assertCount(3, $financial_items);
    self::assertEquals(
      [
        [$line_items[0]['id'], 18.69, $this->getFinancialAccountId($financial_type_id, 'Income Account is'), 'Paid', 18.69],
        [$line_items[0]['id'], 1.31, $this->getFinancialAccountId($financial_type_id, 'Sales Tax Account is'), 'Paid', 1.31],
        [$line_items[1]['id'], 5.0, $this->getFinancialAccountId(1, 'Income Account is'), 'Paid', 5.0],
      ],
      $financial_items
    );
  }

  /**
   * The tax amounts must match those of an equal order entered in CiviCRM and
   * the line items must add up to the contribution amount.
   */
  public function testCreateLineItemsTaxAmountsAddUpToContributionAmount(): void {
    $financial_type_id = $this->createTaxedFinancialType(19.0);

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
      'total_amount' => 50.0,
      'skipLineItem' => 1,
    ]);

    $values = [
      'contribution' => [
        'id' => $contribution['id'],
        'total_amount' => 50.0,
      ],
    ];
    $submission = [
      'products' => [
        [
          'id' => 999997,
          'name' => 'Card set',
          'price' => 10.0,
          'count' => 1,
          'total_value' => 10.0,
        ],
        [
          'id' => 999998,
          'name' => 'Christmas card 1',
          'price' => 15.0,
          'count' => 1,
          'total_value' => 15.0,
        ],
        [
          'id' => 999999,
          'name' => 'Christmas card 5',
          'price' => 15.0,
          'count' => 1,
          'total_value' => 15.0,
        ],
        [
          // Additional donation.
          'id' => -22,
          'name' => 'Additional donation',
          'price' => 10.0,
          'count' => 1,
          'total_value' => 10.0,
        ],
      ],
    ];
    $profile = CRM_Twingle_Profile::createDefaultProfile();
    $profile->setAttribute('shop_financial_type', $financial_type_id);
    $profile->setAttribute('shop_additional_donation_financial_type', 1);

    $line_items = CRM_Twingle_Submission::createLineItems($values, $submission, $profile);
    self::assertCount(4, $line_items);

    $expected = [[8.40, 1.60], [12.61, 2.39], [12.61, 2.39], [10.0, 0.0]];
    foreach ($line_items as $i => $line_item) {
      /** @phpstan-var array<string, mixed> $line_item */
      $line_item = $this->callAPISuccessGetSingle('LineItem', ['id' => $line_item['id']]);
      self::assertEquals($expected[$i], [(float) $line_item['line_total'], (float) $line_item['tax_amount']]);
    }

    /** @phpstan-var array{tax_amount: string} $contribution */
    $contribution = \Civi\Api4\Contribution::get(FALSE)
      ->addSelect('tax_amount')
      ->addWhere('id', '=', $contribution['id'])
      ->execute()
      ->single();
    self::assertEquals(6.39, (float) $contribution['tax_amount']);
    self::assertEquals(50.0, CRM_Price_BAO_LineItem::getLineTotal((int) $contribution['id']));
    self::assertEquals(0.0, CRM_Contribute_BAO_Contribution::getContributionBalance((int) $contribution['id']));
  }

  /**
   * Financial items for pending contributions (e.g. created via CiviSEPA) must
   * be paid by a later payment.
   */
  public function testCreateLineItemsFinancialItemsArePaidLater(): void {
    $financial_type_id = $this->createTaxedFinancialType(7.0);

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
      'contribution_status_id' => 'Pending',
      'skipLineItem' => 1,
    ]);

    $values = [
      'contribution' => [
        'id' => $contribution['id'],
        'total_amount' => 25.0,
      ],
    ];
    $submission = [
      'products' => [
        [
          'id' => 999999,
          'name' => 'Coffee',
          'price' => 10.0,
          'count' => 2,
          'total_value' => 20.0,
        ],
      ],
    ];
    $profile = CRM_Twingle_Profile::createDefaultProfile();
    $profile->setAttribute('shop_financial_type', $financial_type_id);
    $profile->setAttribute('shop_donation_financial_type', 1);

    $line_items = CRM_Twingle_Submission::createLineItems($values, $submission, $profile);

    $financial_items = $this->getFinancialItems($line_items);
    self::assertCount(3, $financial_items);
    foreach ($financial_items as [, , , $status, $assigned_amount]) {
      self::assertSame('Unpaid', $status);
      self::assertNull($assigned_amount);
    }

    $this->callAPISuccess('Payment', 'create', [
      'contribution_id' => $contribution['id'],
      'total_amount' => 25.0,
    ]);

    /** @phpstan-var array{'contribution_status_id:name': string} $contribution */
    $contribution = \Civi\Api4\Contribution::get(FALSE)
      ->addSelect('contribution_status_id:name')
      ->addWhere('id', '=', $contribution['id'])
      ->execute()
      ->single();
    self::assertSame('Completed', $contribution['contribution_status_id:name']);

    self::assertEquals(
      [
        [$line_items[0]['id'], 18.69, $this->getFinancialAccountId($financial_type_id, 'Income Account is'), 'Paid', 18.69],
        [$line_items[0]['id'], 1.31, $this->getFinancialAccountId($financial_type_id, 'Sales Tax Account is'), 'Paid', 1.31],
        [$line_items[1]['id'], 5.0, $this->getFinancialAccountId(1, 'Income Account is'), 'Paid', 5.0],
      ],
      $this->getFinancialItems($line_items)
    );
  }

  /**
   * Get the financial items of the given line items.
   *
   * @phpstan-param list<array<string, mixed>> $line_items
   *
   * @phpstan-return list<array{int, float, int, string, float|null}>
   *   Line item ID, amount, financial account ID, status and the amount
   *   assigned to payments for each financial item.
   */
  private function getFinancialItems(array $line_items): array {
    $financial_items = [];
    $dao = CRM_Core_DAO::executeQuery(
      'SELECT fi.entity_id, fi.amount, fi.financial_account_id, fi.status_id, SUM(eft.amount) AS assigned_amount
      FROM civicrm_financial_item fi
      LEFT JOIN civicrm_entity_financial_trxn eft
        ON eft.entity_table = "civicrm_financial_item" AND eft.entity_id = fi.id
      WHERE fi.entity_table = "civicrm_line_item" AND fi.entity_id IN (%1)
      GROUP BY fi.id
      ORDER BY fi.id',
      [1 => [implode(',', array_filter(array_column($line_items, 'id'), 'is_numeric')), 'CommaSeparatedIntegers']]
    );
    self::assertInstanceOf(CRM_Core_DAO::class, $dao);
    foreach ($dao->fetchAll() as $row) {
      self::assertIsArray($row);
      self::assertIsNumeric($row['entity_id']);
      self::assertIsNumeric($row['amount']);
      self::assertIsNumeric($row['financial_account_id']);
      self::assertIsNumeric($row['status_id']);
      $financial_items[] = [
        (int) $row['entity_id'],
        (float) $row['amount'],
        (int) $row['financial_account_id'],
        (string) CRM_Core_PseudoConstant::getName('CRM_Financial_DAO_FinancialItem', 'status_id', (int) $row['status_id']),
        is_numeric($row['assigned_amount']) ? (float) $row['assigned_amount'] : NULL,
      ];
    }
    return $financial_items;
  }

  /**
   * Get the financial account of a financial type by its relationship.
   */
  private function getFinancialAccountId(int $financial_type_id, string $relationship): int {
    return (int) CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship(
      $financial_type_id,
      $relationship
    );
  }

  /**
   * Create a financial type with a sales tax account.
   *
   * @param float $tax_rate
   *   Tax rate in percent.
   *
   * @return int
   *   The ID of the financial type.
   */
  private function createTaxedFinancialType(float $tax_rate): int {
    /** @phpstan-var array{id: int|string} $financial_type */
    $financial_type = $this->callAPISuccess('FinancialType', 'create', [
      'name' => 'Twingle Shop Taxed',
      'is_active' => 1,
    ]);
    /** @phpstan-var array{id: int|string} $financial_account */
    $financial_account = $this->callAPISuccess('FinancialAccount', 'create', [
      'name' => 'Twingle Sales Tax',
      'financial_account_type_id' => CRM_Core_PseudoConstant::getKey(
        'CRM_Financial_DAO_FinancialAccount',
        'financial_account_type_id',
        'Liability'
      ),
      'is_tax' => 1,
      'tax_rate' => $tax_rate,
      'is_active' => 1,
    ]);
    $this->callAPISuccess('EntityFinancialAccount', 'create', [
      'entity_table' => 'civicrm_financial_type',
      'entity_id' => $financial_type['id'],
      'account_relationship' => CRM_Core_PseudoConstant::getKey(
        'CRM_Financial_DAO_EntityFinancialAccount',
        'account_relationship',
        'Sales Tax Account is'
      ),
      'financial_account_id' => $financial_account['id'],
    ]);
    // Reset the cached tax rates.
    unset(Civi::$statics['CRM_Core_PseudoConstant']['taxRates']);

    return (int) $financial_type['id'];
  }

}
