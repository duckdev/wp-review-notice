<?php

declare( strict_types=1 );

namespace DuckDev\Reviews\Tests\Support;

use DuckDev\Reviews\Exceptions\NoticeException;
use DuckDev\Reviews\Support\KeyPrefixer;
use DuckDev\Reviews\Tests\TestCase;

final class KeyPrefixerTest extends TestCase {

	public function test_key_format(): void {
		$prefixer = new KeyPrefixer( 'my_plugin' );

		$this->assertSame( 'my_plugin', $prefixer->prefix() );
		$this->assertSame( 'my_plugin_time', $prefixer->key( 'time' ) );
		$this->assertSame( 'my_plugin_dismissed', $prefixer->key( 'dismissed' ) );
		$this->assertSame( 'my_plugin_action', $prefixer->key( 'action' ) );
	}

	public function test_trims_prefix(): void {
		$prefixer = new KeyPrefixer( '  foo  ' );

		$this->assertSame( 'foo', $prefixer->prefix() );
	}

	public function test_empty_prefix_throws(): void {
		$this->expectException( NoticeException::class );

		new KeyPrefixer( '   ' );
	}
}
