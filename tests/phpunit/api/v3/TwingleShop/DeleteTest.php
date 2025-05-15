<?php

declare(strict_types = 1);

use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * TwingleShop.Delete API Test Case
 * This is a generic test class implemented with PHPUnit.
 *
 * @group headless
 */
// phpcs:disable Generic.Files.LineLength.TooLong
class api_v3_TwingleShop_DeleteTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, TransactionalInterface {
// phpcs: enable

  use \Civi\Test\Api3TestTrait;

  /**
   * Set up for headless tests.
   *
   * Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
   *
   * See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
   */
  public function setUpHeadless(): CiviEnvBuilder {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

}
