<?php

namespace Drupal\Tests\civictheme\Unit;

use Drupal\Tests\civictheme\Traits\CivicthemeTestHelperTrait;
use Drupal\Tests\UnitTestCase;

/**
 * Class CivicUnitTestBase.
 *
 * Base class for all unit test cases.
 */
abstract class CivicthemeUnitTestBase extends UnitTestCase {

  use CivicthemeTestHelperTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $paths = [
      '../themes/civictheme/civictheme.theme',
      '../themes/contrib/civictheme/civictheme.theme',
      '../themes/custom/civictheme/civictheme.theme',
    ];
    foreach ($paths as $path) {
      if (file_exists($path)) {
        require_once $path;
        break;
      }
    }
  }

}
