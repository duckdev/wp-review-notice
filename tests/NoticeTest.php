<?php

declare( strict_types=1 );

namespace DuckDev\Reviews\Tests;

use DuckDev\Reviews\Contracts\CapabilityCheckerInterface;
use DuckDev\Reviews\Contracts\DismissalStoreInterface;
use DuckDev\Reviews\Contracts\RendererInterface;
use DuckDev\Reviews\Contracts\ScreenResolverInterface;
use DuckDev\Reviews\Contracts\TimerStoreInterface;
use DuckDev\Reviews\Notice;
use DuckDev\Reviews\Support\Config;
use DuckDev\Reviews\Support\KeyPrefixer;

final class NoticeTest extends TestCase {

	private function fakes( bool $screen = true, bool $cap = true, bool $due = true, bool $dismissed = false ): array {
		return array(
			'timer'    => new class( $due ) implements TimerStoreInterface {
				public bool $due;
				public int $started = 0;
				public function __construct( bool $due ) {
					$this->due = $due; }
				public function start( int $days ): void {
					$this->started = $days; }
				public function is_due(): bool {
					return $this->due; }
				public function defer( int $days ): void {}
			},
			'dismiss'  => new class( $dismissed ) implements DismissalStoreInterface {
				public bool $is;
				public function __construct( bool $is ) {
					$this->is = $is; }
				public function is_dismissed(): bool {
					return $this->is; }
				public function dismiss(): void {}
			},
			'screen'   => new class( $screen ) implements ScreenResolverInterface {
				public bool $ok;
				public function __construct( bool $ok ) {
					$this->ok = $ok; }
				public function is_allowed( array $allowed ): bool {
					return $this->ok; }
			},
			'cap'      => new class( $cap ) implements CapabilityCheckerInterface {
				public bool $ok;
				public function __construct( bool $ok ) {
					$this->ok = $ok; }
				public function can( string $capability ): bool {
					return $this->ok; }
			},
			'renderer' => new class() implements RendererInterface {
				public int $calls = 0;
				public function render( Config $config, KeyPrefixer $prefixer ): void {
					++$this->calls; }
			},
		);
	}

	private function notice( array $f ): Notice {
		return new Notice(
			Config::from_array( 'my-plugin', 'My Plugin' ),
			$f['timer'],
			$f['dismiss'],
			$f['screen'],
			$f['cap'],
			$f['renderer']
		);
	}

	public function test_can_show_when_all_conditions_met(): void {
		$f = $this->fakes();
		$this->assertTrue( $this->notice( $f )->can_show() );
	}

	public function test_render_outputs_when_visible(): void {
		$f = $this->fakes();
		$this->notice( $f )->render();
		$this->assertSame( 1, $f['renderer']->calls );
	}

	public function test_render_skipped_when_screen_disallowed(): void {
		$f = $this->fakes( false );
		$this->notice( $f )->render();
		$this->assertSame( 0, $f['renderer']->calls );
	}

	public function test_render_skipped_when_cap_missing(): void {
		$f = $this->fakes( true, false );
		$this->notice( $f )->render();
		$this->assertSame( 0, $f['renderer']->calls );
	}

	public function test_render_skipped_when_not_due(): void {
		$f = $this->fakes( true, true, false );
		$this->notice( $f )->render();
		$this->assertSame( 0, $f['renderer']->calls );
	}

	public function test_render_skipped_when_already_dismissed(): void {
		$f = $this->fakes( true, true, true, true );
		$this->notice( $f )->render();
		$this->assertSame( 0, $f['renderer']->calls );
	}
}
