<?php

declare( strict_types=1 );

namespace DuckDev\Reviews\Tests\Support;

use DuckDev\Reviews\Exceptions\NoticeException;
use DuckDev\Reviews\Support\Config;
use DuckDev\Reviews\Tests\TestCase;

final class ConfigTest extends TestCase {

	public function test_defaults(): void {
		$config = Config::from_array( 'my-plugin', 'My Plugin' );

		$this->assertSame( 'my-plugin', $config->slug() );
		$this->assertSame( 'My Plugin', $config->name() );
		$this->assertSame( 7, $config->days() );
		$this->assertSame( array(), $config->screens() );
		$this->assertSame( 'manage_options', $config->capability() );
		$this->assertSame( '', $config->message() );
		$this->assertSame( 'my_plugin', $config->prefix() );
	}

	public function test_overrides_are_applied(): void {
		$config = Config::from_array(
			'my-plugin',
			'My Plugin',
			array(
				'days'          => 30,
				'screens'       => array( 'dashboard', 'plugins' ),
				'cap'           => 'manage_network',
				'classes'       => array( 'is-dismissible' ),
				'message'       => 'Custom!',
				'action_labels' => array( 'review' => 'Sure' ),
				'prefix'        => 'mp_',
			)
		);

		$this->assertSame( 30, $config->days() );
		$this->assertSame( array( 'dashboard', 'plugins' ), $config->screens() );
		$this->assertSame( 'manage_network', $config->capability() );
		$this->assertSame( 'Custom!', $config->message() );
		$this->assertSame( 'mp_', $config->prefix() );
		$this->assertSame( 'Sure', $config->action_labels()['review'] );
		// Other action labels fall back to defaults.
		$this->assertSame( 'Nope, maybe later', $config->action_labels()['later'] );
	}

	public function test_classes_always_include_base(): void {
		$config = Config::from_array( 'my-plugin', 'My Plugin', array( 'classes' => array( 'is-dismissible' ) ) );

		$this->assertSame( 'notice notice-info is-dismissible', $config->classes() );
	}

	public function test_empty_slug_throws(): void {
		$this->expectException( NoticeException::class );

		Config::from_array( '', 'My Plugin' );
	}

	public function test_empty_name_throws(): void {
		$this->expectException( NoticeException::class );

		Config::from_array( 'my-plugin', '' );
	}
}
