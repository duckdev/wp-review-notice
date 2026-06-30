<?php
/**
 * Base test case wiring Brain\Monkey.
 *
 * Every concrete test extends this class so Brain\Monkey's
 * function-stubbing facilities are set up/torn down automatically
 * and the standard WordPress helpers we touch (`apply_filters`,
 * `__`, `esc_html__`, `esc_html`, `esc_attr`, `esc_url`,
 * `add_query_arg`) have safe defaults that don't require redefining
 * them in every test.
 *
 * @package DuckDev\Reviews\Tests
 */

declare( strict_types=1 );

namespace DuckDev\Reviews\Tests;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Default no-op behaviours for the helpers most tests touch.
		Functions\when( 'apply_filters' )->alias(
			static function ( $hook, $value ) {
				return $value;
			}
		);
		Functions\when( '__' )->returnArg();
		Functions\when( 'esc_html__' )->returnArg();
		Functions\when( 'esc_html' )->returnArg();
		Functions\when( 'esc_attr' )->returnArg();
		Functions\when( 'esc_url' )->returnArg();
		Functions\when( 'sanitize_key' )->returnArg();
		Functions\when( 'wp_unslash' )->returnArg();
		Functions\when( 'wp_kses_post' )->returnArg();
		Functions\when( 'add_query_arg' )->alias(
			static function ( $key, $value ) {
				return '?' . $key . '=' . $value;
			}
		);
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}
}
