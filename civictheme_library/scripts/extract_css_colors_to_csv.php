<?php

/**
 * @file
 * Extract CSS4 color variables and output as CSV.
 *
 * Environment variables:
 * - SCRIPT_QUIET: Set to '1' to suppress verbose messages.
 * - SCRIPT_RUN_SKIP: Set to '1' to skip running of the script. Useful when
 *   unit-testing or requiring this file from other files.
 *
 * Usage:
 * @code
 * php extract_css_colors_to_csv.php path/to/variables.css
 * @endcode
 *
 * phpcs:disable Drupal.Commenting.InlineComment.SpacingBefore
 * phpcs:disable Drupal.Commenting.InlineComment.SpacingAfter
 * phpcs:disable DrupalPractice.Commenting.CommentEmptyLine.SpacingAfter
 */

/**
 * Defines exit codes.
 */
define('EXIT_SUCCESS', 0);
define('EXIT_ERROR', 1);

/**
 * Defines error level to be reported as an error.
 */
define('ERROR_LEVEL', E_USER_WARNING);

/**
 * Main functionality.
 *
 * @SuppressWarnings(PHPMD.MissingImport)
 */
function main(array $argv, int $argc):int {
  if (in_array($argv[1] ?? NULL, ['--help', '-help', '-h', '-?'])) {
    print_help();

    return EXIT_SUCCESS;
  }

  // Show help if not enough or more than required arguments.
  if ($argc < 2 || $argc > 2) {
    print_help();

    return EXIT_ERROR;
  }

  $css_file = trim($argv[1]) ?: '';
  if (empty($css_file) || !is_readable($css_file)) {
    throw new \RuntimeException(sprintf('CSS variables file %s in not readable.', $css_file));
  }

  $content = file_get_contents($css_file);
  if ($content === FALSE) {
    throw new \RuntimeException(sprintf('Unable to read from CSS file %s.', $css_file));
  }

  $vars = collect_variables($content);
  $vars = filter_variables($vars);
  $vars = parse_variables($vars);
  output_csv($vars);

  return EXIT_SUCCESS;
}

/**
 * Print help.
 *
 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
 */
function print_help(): void {
  $script_name = basename(__FILE__);
  print <<<EOF
Extract CSS4 variables into CSV.
--------------------------------

Arguments:
  path/to/variables.css     Path to a file with CSS variables.

Options:
  --help                    This help.

Examples:
  php $script_name path/to/variables.css

EOF;
  print PHP_EOL;
}

/**
 * Collect variables from the provided content.
 *
 * @param string $content
 *   Variables to collect.
 *
 * @return array<string>
 *   Array of collected variables as name=>value.
 */
function collect_variables(string $content): array {
  $vars = [];

  $matches = [];
  preg_match_all('/(--[a-zA-Z0-9-]+)\s*:\s*([^;]+);/i', $content, $matches, PREG_SET_ORDER);

  array_walk($matches, function (array $value) use (&$vars): void {
    if (!empty($value[1])) {
      $vars[trim($value[1])] = trim($value[2] ?? '');
    }
  });

  return $vars;
}

/**
 * Filter variables based on a custom criteria.
 *
 * @return array<string>
 *   Array of filtered variables as name=>value.
 */
function filter_variables(array $vars): array {
  $vars = array_filter($vars, function ($k): bool {
    return strpos($k, '--ct') === 0;
  }, ARRAY_FILTER_USE_KEY);

  return $vars;
}

/**
 * Parse variables from name/value pair into structure.
 *
 * @param array $vars
 *   Array of variables with names as keys and values as values.
 *
 * @return array<int, array<string, mixed>>
 *   Array of parsed variables.
 */
function parse_variables(array $vars): array {
  $variables = [];

  foreach ($vars as $name => $value) {
    try {
      $parsed = parse_variable_name($name, 'ct');
    }
    catch (\Exception $e) {
      // Ignore all variables with incorrectly parsed names.
      continue;
    }
    if (!$parsed) {
      continue;
    }
    $parsed['value'] = $value;

    $variables[] = $parsed;
  }

  return $variables;
}

/**
 * Output variable as CSV into specified stream.
 *
 * @param array $vars
 *   Array of variables.
 * @param string $stream
 *   Optional string name. Defaults to 'php://output'.
 *
 * @throws \Exception
 *   If no values were provided.
 *
 * @SuppressWarnings(PHPMD.MissingImport)
 */
function output_csv(array $vars, $stream = 'php://output'): void {
  if (empty($vars)) {
    throw new \Exception('No variables found');
  }

  $header = reset($vars);
  $header = array_keys($header);

  $out = fopen($stream, 'w');

  if ($out === FALSE) {
    throw new \Exception(sprintf('Unable to open stream %s for writing.', $stream));
  }

  fputcsv($out, $header);
  foreach ($vars as $line) {
    fputcsv($out, $line);
  }

  fclose($out);
}

/**
 * Parse variable name.
 *
 * Examples of variables:
 *
 * @code
 * ct-[component]-[theme]-[?subcomponent]-[?state]-[rule]
 * --ct-collapsible-light-background-color
 * --ct-button-light-primary-active-background-color
 * @endcode
 *
 * @param string $name
 *   The variable name.
 * @param string $prefix
 *   Optional prefix to remove from the variable name.
 *
 * @return string[]
 *   Array of parsed variable name with the following keys:
 *   - component: (string) Component name.
 *   - theme: (string) Component theme.
 *   - subcomponent: (string) Optional sub-component name (space delimited).
 *   - state: (string) Optional state.
 *   - rule: (string) Rule.
 *
 * @throws \Exception
 *   - If component name is missing.
 *   - If theme is missing.
 *   - If rule is missing.
 *
 * @SuppressWarnings(PHPMD.MissingImport)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.ElseExpression)
 */
function parse_variable_name(string $name, string $prefix = NULL): array {
  if (empty($name)) {
    throw new \Exception('Empty name provided.');
  }

  $defaults = [
    'component' => '',
    'theme' => '',
    'subcomponent' => '',
    'state' => '',
    'rule' => '',
  ];

  // Remove '--'.
  $name = strpos($name, '--') === 0 ? substr($name, 2) : $name;

  $parts = explode('-', $name);
  // Remove prefix.
  if (!empty($prefix) && $parts[0] == $prefix) {
    unset($parts[0]);
    $parts = array_values($parts);
  }

  if (empty($parts)) {
    throw new \Exception(sprintf('Incorrectly named variable %s: component name is missing.', $name));
  }

  // Filter by rule - maybe we do not need this variable at all.
  $allowed_rules = [
    'color',
    'background-color',
    'border-color',
    'border-left-color',
    'border-top-color',
    'border-bottom-color',
    'border-right-color',
  ];

  foreach ($allowed_rules as $k => $v) {
    $allowed_rules[$k] = explode('-', $v);
  }

  // @phpstan-ignore-next-line
  usort($allowed_rules, function (array $a, array $b): int {
    return count($b) - count($a);
  });

  foreach ($allowed_rules as $allowed_rule) {
    // @phpstan-ignore-next-line
    if (count($parts) <= count($allowed_rule)) {
      continue;
    }

    $matched_count = 0;
    // @phpstan-ignore-next-line
    $allowed_rule = array_reverse($allowed_rule);
    foreach ($allowed_rule as $allowed_rule_idx => $allowed_rule_part) {
      if ($parts[count($parts) - 1 - $allowed_rule_idx] == $allowed_rule_part) {
        $matched_count++;
      }
      else {
        break;
      }
    }

    if ($matched_count != count($allowed_rule)) {
      continue;
    }

    $defaults['rule'] = implode('-', array_reverse($allowed_rule));
    $parts = array_slice($parts, 0, -1 * count($allowed_rule));
    break;
  }

  if (empty($defaults['rule'])) {
    throw new \Exception(sprintf('Incorrectly named variable %s: rule is missing.', $name));
  }

  // Find theme.
  $theme_idx = array_search('light', $parts);
  $theme_idx = $theme_idx === FALSE ? array_search('dark', $parts) : $theme_idx;
  if ($theme_idx === FALSE) {
    throw new \Exception(sprintf('Incorrectly named variable %s: theme is missing.', $name));
  }
  $defaults['theme'] = $parts[$theme_idx];

  $component_name = implode(' ', array_slice($parts, 0, $theme_idx));
  if (empty($component_name)) {
    throw new \Exception(sprintf('Incorrectly named variable %s: component name is missing.', $name));
  }
  $defaults['component'] = $component_name;
  $parts = array_slice($parts, $theme_idx + 1);

  if (count($parts) > 0) {
    $allowed_states = [
      'active',
      'hover',
      'disabled',
      'visited',
    ];
    if (in_array($parts[count($parts) - 1], $allowed_states)) {
      $defaults['state'] = $parts[count($parts) - 1];
      $parts = array_slice($parts, 0, -1);
    }
  }

  if (count($parts) > 0) {
    $defaults['subcomponent'] = implode(' ', $parts);
  }

  return $defaults;
}

// ////////////////////////////////////////////////////////////////////////// //
//                                UTILITIES                                   //
// ////////////////////////////////////////////////////////////////////////// //

/**
 * Show a verbose message.
 *
 * @SuppressWarnings(PHPMD.MissingImport)
 */
function verbose(): void {
  if (getenv('SCRIPT_QUIET') != '1') {
    print call_user_func_array('sprintf', func_get_args()) . PHP_EOL;
  }
}

// ////////////////////////////////////////////////////////////////////////// //
//                                ENTRYPOINT                                  //
// ////////////////////////////////////////////////////////////////////////// //

ini_set('display_errors', 1);

if (PHP_SAPI != 'cli' || !empty($_SERVER['REMOTE_ADDR'])) {
  die('This script can be only ran from the command line.');
}

// Custom error handler to catch errors based on set ERROR_LEVEL.
// @phpstan-ignore-next-line
set_error_handler(function ($severity, $message, $file, $line): void {
  if (!(error_reporting() & $severity)) {
    // This error code is not included in error_reporting.
    return;
  }
  throw new ErrorException($message, 0, $severity, $file, $line);
});

// Allow to skip the script run.
if (getenv('SCRIPT_RUN_SKIP') != 1) {
  try {
    $code = main($argv, $argc);
    exit($code);
  }
  catch (\ErrorException $exception) {
    if ($exception->getSeverity() <= ERROR_LEVEL) {
      print PHP_EOL . 'RUNTIME ERROR: ' . $exception->getMessage() . PHP_EOL;
      exit($exception->getCode() == 0 ? EXIT_ERROR : $exception->getCode());
    }
  }
  catch (\Exception $exception) {
    print PHP_EOL . 'ERROR: ' . $exception->getMessage() . PHP_EOL;
    exit($exception->getCode() == 0 ? EXIT_ERROR : $exception->getCode());
  }
}
