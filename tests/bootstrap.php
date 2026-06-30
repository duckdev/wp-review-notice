<?php
/**
 * PHPUnit bootstrap.
 *
 * Defines the WordPress constants the library guards against and
 * loads the Composer autoloader. Brain\Monkey is initialised per
 * test in {@see \DuckDev\Reviews\Tests\TestCase}.
 *
 * @package DuckDev\Reviews\Tests
 */

declare( strict_types=1 );

if ( ! defined( 'WPINC' ) ) {
	define( 'WPINC', 'wp-includes' );
}

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/wp/' );
}

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}

if ( ! defined( 'FILTER_UNSAFE_RAW' ) ) {
	define( 'FILTER_UNSAFE_RAW', 0 );
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/TestCase.php';
