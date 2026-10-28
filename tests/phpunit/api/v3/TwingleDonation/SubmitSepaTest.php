<?php
/*------------------------------------------------------------+
| SYSTOPIA Twingle Integration                                |
| Copyright (C) 2026 SYSTOPIA                                 |
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

use PHPUnit\Framework\TestCase;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * TwingleDonation.Submit with CiviSEPA.
 *
 * @group headless
 * @covers ::civicrm_api3_twingle_donation_Submit
 */
class api_v3_TwingleDonation_SubmitSepaTest extends TestCase implements HeadlessInterface, TransactionalInterface {

  private const PROJECT_ID = 'tw583f49b20c7bf';

  /**
   * {@inheritDoc}
   */
  public function setUpHeadless(): CiviEnvBuilder {
    return \Civi\Test::headless()
      ->install(['de.systopia.xcm', 'org.project60.sepa'])
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * The account holder Twingle submits ends up on the SEPA mandate.
   */
  public function testMandateAccountHolder(): void {
    \Civi::settings()->set('twingle_use_sepa', TRUE);
    $creditor_id = civicrm_api3('SepaCreditor', 'getvalue', ['return' => 'id']);
    self::assertIsNumeric($creditor_id);
    civicrm_api3('SepaCreditor', 'create', [
      'id' => $creditor_id,
      'creditor_type' => 'SEPA',
      'category' => NULL,
      'pi_ooff' => CRM_Sepa_Logic_PaymentInstruments::getClassicSepaPaymentInstruments()['OOFF'],
    ]);
    $profile = CRM_Twingle_Profile::getProfileForProject(self::PROJECT_ID);
    $profile->setAttribute('pi_debit_automatic', 'sepa');
    $profile->setAttribute('sepa_creditor_id', (string) $creditor_id);
    $profile->saveProfile();

    /** @var array{values: array{sepa_mandate: array{id: int|string}}} $result */
    $result = civicrm_api3('TwingleDonation', 'submit', [
      'project_id' => self::PROJECT_ID,
      'trx_id' => 'SEPA1',
      'confirmed_at' => '20260424100831',
      'purpose' => 'Schulprojekte',
      'amount' => 1000,
      'currency' => 'EUR',
      'user_email' => 'test@example.org',
      'user_country' => 'DE',
      'user_language' => 'de',
      'payment_method' => 'debit_automatic',
      'donation_rhythm' => 'one_time',
      'is_anonymous' => 0,
      'newsletter' => 0,
      'postinfo' => 0,
      'donation_receipt' => 0,
      'debit_iban' => 'DE89370400440532013000',
      'debit_bic' => 'COBADEFFXXX',
      'debit_mandate_reference' => 'TW-SEPA1',
      'debit_account_holder' => 'Example Ltd.',
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
    ]);

    self::assertSame('Example Ltd.', civicrm_api3('SepaMandate', 'getvalue', [
      'id' => $result['values']['sepa_mandate']['id'],
      'return' => 'account_holder',
    ]));
  }

}
