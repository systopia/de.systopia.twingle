<?php
/*------------------------------------------------------------+
| SYSTOPIA Twingle Integration                                |
| Copyright (C) 2025 SYSTOPIA                                 |
| Author: J. Schuppe (schuppe@systopia.de)                    |
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

use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;
use Civi\Api4\Contribution;
use CRM_Twingle_ExtensionUtil as E;

/**
 * TwingleDonation.Submit API Test Case
 * This is a generic test class implemented with PHPUnit.
 *
 * @group headless
 * @covers ::\civicrm_api3_twingle_donation_Submit()
 */
// phpcs:disable Generic.Files.LineLength.TooLong
class api_v3_TwingleDonation_SubmitTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, TransactionalInterface {
// phpcs: enable

  use \Civi\Test\Api3TestTrait;

  private const PROJECT_ID = 'tw583f49b20c7bf';

  private const BOOKING_DATE = '20250424080844';

  private const CONFIRMATION_DATE = '20250424100831';

  private const SUBMISSION = [
    'project_id' => self::PROJECT_ID,
    'trx_id' => 'GL947LC',
    'parent_trx_id' => NULL,
    'confirmed_at' => self::CONFIRMATION_DATE,
    'booked_at' => self::BOOKING_DATE,
    'purpose' => 'Schulprojekte',
    'amount' => 1000,
    'currency' => 'EUR',
    'remarks' => NULL,
    'user_email' => 'test@example.org',
    'user_country' => 'DE',
    'user_language' => 'de',
    'payment_method' => 'creditcard',
    'donation_rhythm' => 'one_time',
    'is_anonymous' => 0,
    'newsletter' => 0,
    'postinfo' => 0,
    'donation_receipt' => 0,
    'user_title' => NULL,
    'user_firstname' => NULL,
    'user_lastname' => NULL,
    'user_gender' => NULL,
    'user_street' => NULL,
    'user_postal_code' => NULL,
    'user_city' => NULL,
    'user_telephone' => NULL,
    'user_company' => NULL,
    'user_extrafield' => NULL,
  ];

  /**
   * {@inheritDoc}
   */
  public function setUpHeadless(): CiviEnvBuilder {
    return \Civi\Test::headless()
      ->install(['de.systopia.xcm'])
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * Test contribution date selection.
   */
  public function testContributionDate(): void {
    /** @var array{values: array{contribution: array{id: int|string, receive_date: string}}} $result */
    $result = civicrm_api3('TwingleDonation', 'submit', self::SUBMISSION);
    // Assert that booking date has been used as contribution date when configured (profile default).
    self::assertEquals(self::BOOKING_DATE, $result['values']['contribution']['receive_date']);

    // TODO: Test date selection with a submission without a booking date.

    // Delete contribution and set profile to not use booking date.
    Contribution::delete(FALSE)
      ->addWhere('id', '=', $result['values']['contribution']['id'])
      ->execute();
    $profile = \CRM_Twingle_Profile::getProfileForProject(self::PROJECT_ID);
    $profile->setAttribute('use_booking_date', FALSE);
    $profile->saveProfile();

    /** @var array{values: array{contribution: array{id: int|string, receive_date: string}}} $result */
    $result = civicrm_api3('TwingleDonation', 'submit', self::SUBMISSION);
    // Assert that confirmation date has been used as contribution date when configured.
    self::assertEquals(self::CONFIRMATION_DATE, $result['values']['contribution']['receive_date']);
  }

}
