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
use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * TwingleDonation.Cancel API Test Case
 *
 * @group headless
 * @covers ::civicrm_api3_twingle_donation_Cancel
 */
class api_v3_TwingleDonation_CancelTest extends TestCase implements HeadlessInterface, TransactionalInterface {

  use Api3TestTrait;

  private const PROJECT_ID = 'tw583f49b20c7bf';

  private const BOOKING_DATE = '20250424080844';

  private const CONFIRMATION_DATE = '20250424100831';

  private const CANCELLED_DATE = '20250425100000';

  private const SUBMISSION = [
    'project_id' => self::PROJECT_ID,
    'trx_id' => 'CANCEL_TRX_1',
    'parent_trx_id' => NULL,
    'confirmed_at' => self::CONFIRMATION_DATE,
    'booked_at' => self::BOOKING_DATE,
    'purpose' => 'Schulprojekte',
    'amount' => 1000,
    'currency' => 'EUR',
    'remarks' => NULL,
    'user_email' => 'cancel_donor@example.org',
    'user_country' => 'DE',
    'user_language' => 'de',
    'payment_method' => 'creditcard',
    'donation_rhythm' => 'one_time',
    'is_anonymous' => 0,
    'newsletter' => 0,
    'postinfo' => 0,
    'donation_receipt' => 0,
    'user_title' => NULL,
    'user_firstname' => 'Cancel',
    'user_lastname' => 'Donor',
    'user_gender' => NULL,
    'user_street' => NULL,
    'user_postal_code' => NULL,
    'user_city' => NULL,
    'user_telephone' => NULL,
    'user_company' => NULL,
    'user_extrafield' => NULL,
  ];

  public function setUpHeadless(): CiviEnvBuilder {
    return \Civi\Test::headless()
      ->install(['de.systopia.xcm', 'de.systopia.identitytracker'])
      ->installMe(__DIR__)
      ->apply();
  }

  public function testCancelDonationWithPersonId(): void {
    /** @var array{values: array{contact: int|string, contribution: array{id: int|string}}} $submitResult */
    $submitResult = civicrm_api3('TwingleDonation', 'submit', self::SUBMISSION);
    $contactId = (int) $submitResult['values']['contact'];
    $contributionId = (int) $submitResult['values']['contribution']['id'];
    self::assertNotEmpty($contactId);
    self::assertNotEmpty($contributionId);

    /** @var array{is_error: int} $cancelResult */
    $cancelResult = civicrm_api3('TwingleDonation', 'cancel', [
      'project_id' => self::PROJECT_ID,
      'trx_id' => 'CANCEL_TRX_1',
      'person_id' => 'P-CANCEL-123',
      'cancelled_at' => self::CANCELLED_DATE,
      'cancel_reason' => 'Donor requested cancellation',
    ]);
    self::assertEquals(0, $cancelResult['is_error']);

    /** @var array{contribution_status_id: int|string} $cancelledContribution */
    $cancelledContribution = civicrm_api3('Contribution', 'getsingle', [
      'id' => $contributionId,
    ]);
    self::assertEquals(3, (int) $cancelledContribution['contribution_status_id']);

    /** @var array{values: array<int|string, array{id: int|string}>} $identitySearch */
    $identitySearch = civicrm_api3('Contact', 'findbyidentity', [
      'identifier_type' => 'person_id',
      'identifier' => 'P-CANCEL-123',
    ]);
    self::assertCount(1, $identitySearch['values']);
    $foundContact = reset($identitySearch['values']);
    self::assertEquals($contactId, (int) $foundContact['id']);
  }

  public function testCancelDonationLogsWarningWhenAddIdentityFails(): void {
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

    $listener = function(PrepareEvent $event): void {
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
      $submission['trx_id'] = 'CANCEL_TRX_LOG_TEST';
      /** @var array{values: array{contact: int|string}} $submitResult */
      $submitResult = civicrm_api3('TwingleDonation', 'submit', $submission);
      $contactId = (int) $submitResult['values']['contact'];
      self::assertNotEmpty($contactId);

      /** @var array{is_error: int} $cancelResult */
      $cancelResult = civicrm_api3('TwingleDonation', 'cancel', [
        'project_id' => self::PROJECT_ID,
        'trx_id' => 'CANCEL_TRX_LOG_TEST',
        'person_id' => 'P-CANCEL-LOG-999',
        'cancelled_at' => self::CANCELLED_DATE,
        'cancel_reason' => 'Testing log on failure',
      ]);
      self::assertEquals(0, $cancelResult['is_error']);
    }
    finally {
      Civi::dispatcher()->removeListener('civi.api.prepare', $listener);
      if ($origChannels !== NULL) {
        $logRef->setValue($logManager, $origChannels);
      }
    }
  }

}
