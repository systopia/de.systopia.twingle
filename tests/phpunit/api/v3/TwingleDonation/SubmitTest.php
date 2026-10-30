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

use Civi\API\Event\PrepareEvent;
use Civi\Core\LogManager;
use Civi\Test\Api3TestTrait;
use PHPUnit\Framework\TestCase;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;
use Civi\Api4\Contribution;
use CRM_Twingle_ExtensionUtil as E;
use Psr\Log\LoggerInterface;

/**
 * TwingleDonation.Submit API Test Case
 * This is a generic test class implemented with PHPUnit.
 *
 * @group headless
 * @covers ::civicrm_api3_twingle_donation_Submit
 */
class api_v3_TwingleDonation_SubmitTest extends TestCase implements HeadlessInterface, TransactionalInterface {

  use Api3TestTrait;

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
      ->install(['de.systopia.xcm', 'de.systopia.identitytracker'])
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
    // The returned format is different depending on the CiviCRM version.
    self::assertEquals(
      new DateTime(self::BOOKING_DATE),
      new DateTime($result['values']['contribution']['receive_date'])
    );

    // TODO: Test date selection with a submission without a booking date.

    // Delete contribution and set profile to not use booking date.
    Contribution::delete(FALSE)
      ->addWhere('id', '=', $result['values']['contribution']['id'])
      ->execute();
    $profile = CRM_Twingle_Profile::getProfileForProject(self::PROJECT_ID);
    $profile->setAttribute('use_booking_date', FALSE);
    $profile->saveProfile();

    /** @var array{values: array{contribution: array{id: int|string, receive_date: string}}} $result */
    $result = civicrm_api3('TwingleDonation', 'submit', self::SUBMISSION);
    // Assert that confirmation date has been used as contribution date when configured.
    // The returned format is different depending on the CiviCRM version.
    self::assertEquals(
      new DateTime(self::CONFIRMATION_DATE),
      new DateTime($result['values']['contribution']['receive_date'])
    );
  }

  /**
   * Test Person ID submission, managed option values, and identity persistence.
   */
  public function testPersonIdSubmissionAndIdentity(): void {
    /** @var array{value: string} $optionValue */
    $optionValue = civicrm_api3('OptionValue', 'getsingle', [
      'option_group_name' => 'contact_id_history_type',
      'name' => 'person_id',
    ]);
    self::assertEquals('person_id', $optionValue['value']);

    $submission = self::SUBMISSION;
    $submission['trx_id'] = 'TW_TRX_ID_1';
    $submission['person_id'] = 'P-DONOR1001';
    $submission['user_email'] = 'twingle_donor@example.org';
    $submission['user_firstname'] = 'Twingle';
    $submission['user_lastname'] = 'Donor';

    /** @var array{values: array{contact: int|string, contribution: array{id: int|string}}} $result */
    $result = civicrm_api3('TwingleDonation', 'submit', $submission);
    $contact_id = (int) $result['values']['contact'];
    self::assertNotEmpty($contact_id);

    /** @var array{values: array<int|string, array{id: int|string}>} $identitySearch */
    $identitySearch = civicrm_api3('Contact', 'findbyidentity', [
      'identifier_type' => 'person_id',
      'identifier' => 'P-DONOR1001',
    ]);
    self::assertCount(1, $identitySearch['values']);
    $foundContact = reset($identitySearch['values']);
    self::assertEquals($contact_id, (int) $foundContact['id']);
  }

  public function testPersonIdMatcherDeduplicatesContact(): void {
    $xcmConfig = CRM_Xcm_Configuration::getConfigProfile();
    $xcmConfig->setRules(['CRM_Xcm_Matcher_PersonIdMatcher']);
    $xcmConfig->store();

    $firstSubmission = self::SUBMISSION;
    $firstSubmission['trx_id'] = 'TW_TRX_FIRST';
    $firstSubmission['person_id'] = 'P-MATCH-999';
    $firstSubmission['user_email'] = 'first_donor@example.org';
    $firstSubmission['user_firstname'] = 'Original';
    $firstSubmission['user_lastname'] = 'Name';

    /** @var array{values: array{contact: int|string}} $firstResult */
    $firstResult = civicrm_api3('TwingleDonation', 'submit', $firstSubmission);
    $firstContactId = (int) $firstResult['values']['contact'];
    self::assertNotEmpty($firstContactId);

    $secondSubmission = self::SUBMISSION;
    $secondSubmission['trx_id'] = 'TW_TRX_SECOND';
    $secondSubmission['person_id'] = 'P-MATCH-999';
    $secondSubmission['user_email'] = 'different_email@example.org';
    $secondSubmission['user_firstname'] = 'Changed';
    $secondSubmission['user_lastname'] = 'Name';

    /** @var array{values: array{contact: int|string}} $secondResult */
    $secondResult = civicrm_api3('TwingleDonation', 'submit', $secondSubmission);
    $secondContactId = (int) $secondResult['values']['contact'];

    self::assertEquals($firstContactId, $secondContactId);
  }

  public function testPersonIdSubmissionLogsWarningWhenAddIdentityFails(): void {
    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects(self::once())
      ->method('warning')
      ->with(
        self::matchesRegularExpression(
          '/Failed to persist person_id for contact \d+: Simulated addidentity failure/'
        ),
        self::arrayHasKey('exception')
      );

    $origChannels = NULL;
    $logRef = NULL;
    $logManager = Civi::container()->get('psr_log_manager');
    if ($logManager instanceof LogManager) {
      $logRef = new ReflectionProperty(LogManager::class, 'channels');
      $logRef->setAccessible(TRUE);
      $origChannels = $logRef->getValue($logManager);
      $logRef->setValue($logManager, ['default' => $logger]);
    }

    $listener = function (PrepareEvent $event): void {
      $apiRequest = $event->getApiRequest();
      if (
        is_array($apiRequest)
        && ($apiRequest['entity'] ?? NULL) === 'Contact'
        && ($apiRequest['action'] ?? NULL) === 'addidentity'
      ) {
        throw new CRM_Core_Exception('Simulated addidentity failure');
      }
    };
    Civi::dispatcher()->addListener('civi.api.prepare', $listener);

    try {
      $submission = self::SUBMISSION;
      $submission['trx_id'] = 'TW_TRX_LOG_TEST';
      $submission['person_id'] = 'P-DONORFAIL';
      $submission['user_email'] = 'twingle_log_test@example.org';
      $submission['user_firstname'] = 'Log';
      $submission['user_lastname'] = 'Tester';

      /** @var array{values: array{contact: int|string, contribution: array{id: int|string}}} $result */
      $result = civicrm_api3('TwingleDonation', 'submit', $submission);
      $contact_id = (int) $result['values']['contact'];
      self::assertNotEmpty($contact_id);

      /** @var array{first_name: string, last_name: string} $contact */
      $contact = $this->callAPISuccess('Contact', 'getsingle', ['id' => $contact_id]);
      self::assertEquals('Log', $contact['first_name']);
    }
    finally {
      Civi::dispatcher()->removeListener('civi.api.prepare', $listener);
      if ($origChannels !== NULL) {
        $logRef->setValue($logManager, $origChannels);
      }
    }
  }

}
