<?php

namespace Drupal\Tests\civictheme\Unit;

use Drupal\civictheme\CivicthemeUtility;

/**
 * Class CivicthemeUtilityUnitTest.
 *
 * Test cases for utility functions.
 *
 * @group CivicTheme
 * @group site:unit
 */
class CivicthemeUtilityUnitTest extends CivicthemeUnitTestBase {

  /**
   * Test for CivicthemeUtility::htmlAttributesToArray().
   *
   * @dataProvider dataProviderHtmlAttributesToArray
   * @SuppressWarnings(PHPMD.StaticAccess)
   */
  public function testHtmlAttributesToArray($string, $expected) {
    $actual = CivicthemeUtility::htmlAttributesToArray($string);

    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for testHtmlAttributesToArray().
   */
  public function dataProviderHtmlAttributesToArray() {
    return [
      // Empty.
      ['', []],
      // Minimal.
      ['a="b"', ['a' => 'b']],
      // With a space.
      ['a = "b"', ['a' => 'b']],
      // Multiple.
      ['a = "b" c="d"', ['a' => 'b', 'c' => 'd']],
      // Vertical spacings.
      ['a = "b c d" e="f g"', ['a' => 'b c d', 'e' => 'f g']],
      // Numeric.
      ['a = "b1 c2 d3" e="f4 g5"', ['a' => 'b1 c2 d3', 'e' => 'f4 g5']],
      // Repeating.
      ['a="b" a="c"', ['a' => 'c']],
      // No value - only works when the value is set to an empty string.
      ['a=""', ['a' => NULL]],
      // HTML tag.
      ['<tag a="" b="c">', ['a' => NULL, 'b' => 'c']],
      ['<tag a="" b="c" />', ['a' => NULL, 'b' => 'c']],
    ];
  }

  /**
   * Test for CivicthemeUtility::multilineToArray().
   *
   * @dataProvider dataProviderMultilineToArray
   * @SuppressWarnings(PHPMD.StaticAccess)
   */
  public function testMultilineToArray($string, $expected) {
    $actual = $actual = CivicthemeUtility::multilineToArray($string);
    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for testMultilineToArray().
   */
  public function dataProviderMultilineToArray() {
    return [
      ['', []],
      [' ', []],
      ['a', ['a']],
      [
        'a
        b', ['a', 'b'],
      ],
      [
        'a aa
        b', ['a aa', 'b'],
      ],
      [
        'a aa
        b
        c', ['a aa', 'b', 'c'],
      ],
      [
        'a aa
        b
        c', ['a aa', 'b', 'c'],
      ],
      // Array as input.
      [
        ['a', 'b'], ['a', 'b'],
      ],
    ];
  }

  /**
   * Test for CivicthemeUtility::arrayToMultiline().
   *
   * @dataProvider dataProviderArrayToMultiline
   * @SuppressWarnings(PHPMD.StaticAccess)
   */
  public function testArrayToMultiline($array, $expected) {
    $actual = CivicthemeUtility::arrayToMultiline($array);
    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for testArrayToMultiline().
   */
  public function dataProviderArrayToMultiline() {
    return [
      [[], ''],
      [[''], ''],
      [['', ''], ''],
      [[' ', ''], ' '],
      [['a'], 'a'],
      [
        ['a', 'b'], 'a' . PHP_EOL . 'b',
      ],
      [[' a ', 'b'], ' a ' . PHP_EOL . 'b'],
      [[' a ', '', 'b'], ' a ' . PHP_EOL . 'b'],
      [[' a ', ' ', 'b'], ' a ' . PHP_EOL . ' ' . PHP_EOL . 'b'],
      // String as input.
      ['', ''],
      ['a', 'a'],
    ];
  }

  /**
   * Test for arrayMergeKeysValues().
   *
   * @dataProvider dataProviderArrayMergeKeysValues
   * @SuppressWarnings(PHPMD.StaticAccess)
   */
  public function testArrayMergeKeysValues(array $array, $separator, $expected) {
    $actual = CivicthemeUtility::arrayMergeKeysValues($array, $separator);
    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for testArrayMergeKeysValues().
   */
  public function dataProviderArrayMergeKeysValues() {
    return [
      [[], ' ', []],
      [['a', 'b'], ' ', ['0 a', '1 b']],
      [[1, 2, 3], ' ', ['0 1', '1 2', '2 3']],
      [['a', 2, 'd'], ' ', ['0 a', '1 2', '2 d']],
    ];
  }

  /**
   * Test for toLabel().
   *
   * @dataProvider dataProviderToLabel
   * @SuppressWarnings(PHPMD.StaticAccess)
   */
  public function testToLabel($string, $expected) {
    $actual = CivicthemeUtility::toLabel($string);
    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for testToLabel().
   */
  public function dataProviderToLabel() {
    return [
      ['', ''],
      ['hello', 'Hello'],
      ['hello_world', 'Hello world'],
      ['Hello earth', 'Hello earth'],
      ['Hello-world', 'Hello-world'],
    ];
  }

}
