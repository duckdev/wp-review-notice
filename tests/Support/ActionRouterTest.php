<?php

declare( strict_types=1 );

namespace DuckDev\Reviews\Tests\Support;

use DuckDev\Reviews\Contracts\CapabilityCheckerInterface;
use DuckDev\Reviews\Contracts\DismissalStoreInterface;
use DuckDev\Reviews\Contracts\TimerStoreInterface;
use DuckDev\Reviews\Support\ActionRouter;
use DuckDev\Reviews\Support\Config;
use DuckDev\Reviews\Support\KeyPrefixer;
use DuckDev\Reviews\Tests\TestCase;

final class ActionRouterTest extends TestCase {

	private Config $config;
	private KeyPrefixer $prefixer;

	protected function setUp(): void {
		parent::setUp();

		$this->config   = Config::from_array( 'my-plugin', 'My Plugin', array( 'days' => 7 ) );
		$this->prefixer = new KeyPrefixer( $this->config->prefix() );
	}

	private function router(
		TimerStoreInterface $timer,
		DismissalStoreInterface $dismissal,
		bool $can = true
	): ActionRouter {
		$capability = new class( $can ) implements CapabilityCheckerInterface {
			private bool $can;
			public function __construct( bool $can ) {
				$this->can = $can; }
			public function can( string $capability ): bool {
				return $this->can; }
		};

		return new ActionRouter( $this->config, $this->prefixer, $timer, $dismissal, $capability );
	}

	private function spyStores(): array {
		$timer = new class() implements TimerStoreInterface {
			public int $deferred = 0;
			public function start( int $days ): void {}
			public function is_due(): bool {
				return false; }
			public function defer( int $days ): void {
				$this->deferred = $days; }
		};

		$dismissal = new class() implements DismissalStoreInterface {
			public bool $dismissed = false;
			public function is_dismissed(): bool {
				return $this->dismissed; }
			public function dismiss(): void {
				$this->dismissed = true; }
		};

		return array( $timer, $dismissal );
	}

	public function test_dispatch_defers_on_later(): void {
		[ $timer, $dismissal ]                    = $this->spyStores();
		$_GET[ $this->prefixer->key( 'action' ) ] = 'later';

		$this->router( $timer, $dismissal )->dispatch();

		$this->assertSame( 14, $timer->deferred );
		$this->assertFalse( $dismissal->dismissed );

		unset( $_GET[ $this->prefixer->key( 'action' ) ] );
	}

	public function test_dispatch_dismisses_on_dismiss(): void {
		[ $timer, $dismissal ]                    = $this->spyStores();
		$_GET[ $this->prefixer->key( 'action' ) ] = 'dismiss';

		$this->router( $timer, $dismissal )->dispatch();

		$this->assertSame( 0, $timer->deferred );
		$this->assertTrue( $dismissal->dismissed );

		unset( $_GET[ $this->prefixer->key( 'action' ) ] );
	}

	public function test_dispatch_noop_when_no_action(): void {
		[ $timer, $dismissal ] = $this->spyStores();

		$this->router( $timer, $dismissal )->dispatch();

		$this->assertSame( 0, $timer->deferred );
		$this->assertFalse( $dismissal->dismissed );
	}

	public function test_dispatch_gated_by_capability(): void {
		[ $timer, $dismissal ]                    = $this->spyStores();
		$_GET[ $this->prefixer->key( 'action' ) ] = 'dismiss';

		$this->router( $timer, $dismissal, false )->dispatch();

		$this->assertFalse( $dismissal->dismissed );

		unset( $_GET[ $this->prefixer->key( 'action' ) ] );
	}
}
