<?php

declare( strict_types=1 );

namespace DuckDev\Reviews\Tests\Storage;

use Brain\Monkey\Functions;
use DuckDev\Reviews\Storage\SiteOptionTimerStore;
use DuckDev\Reviews\Support\KeyPrefixer;
use DuckDev\Reviews\Tests\TestCase;

final class SiteOptionTimerStoreTest extends TestCase {

	private function store(): SiteOptionTimerStore {
		return new SiteOptionTimerStore( new KeyPrefixer( 'p' ) );
	}

	public function test_start_seeds_schedule_when_unset(): void {
		Functions\expect( 'get_site_option' )->once()->with( 'p_review_time' )->andReturn( false );
		Functions\expect( 'update_site_option' )
			->once()
			->with( 'p_review_time', \Mockery::type( 'int' ) )
			->andReturn( true );

		$this->store()->start( 7 );
	}

	public function test_start_is_noop_when_already_seeded(): void {
		Functions\expect( 'get_site_option' )->once()->with( 'p_review_time' )->andReturn( time() + 1000 );
		Functions\expect( 'update_site_option' )->never();

		$this->store()->start( 7 );
	}

	public function test_is_due_false_when_unseeded(): void {
		Functions\expect( 'get_site_option' )->once()->andReturn( false );

		$this->assertFalse( $this->store()->is_due() );
	}

	public function test_is_due_true_when_stored_timestamp_in_past(): void {
		Functions\expect( 'get_site_option' )->once()->andReturn( time() - 10 );

		$this->assertTrue( $this->store()->is_due() );
	}

	public function test_is_due_false_when_stored_timestamp_in_future(): void {
		Functions\expect( 'get_site_option' )->once()->andReturn( time() + 10000 );

		$this->assertFalse( $this->store()->is_due() );
	}

	public function test_is_due_does_not_mutate_storage(): void {
		Functions\expect( 'get_site_option' )->once()->andReturn( false );
		Functions\expect( 'update_site_option' )->never();

		$this->store()->is_due();
	}

	public function test_defer_writes_future_timestamp(): void {
		Functions\expect( 'update_site_option' )
			->once()
			->with(
				'p_review_time',
				\Mockery::on(
					static function ( $value ) {
						return is_int( $value ) && $value > time();
					}
				)
			)
			->andReturn( true );

		$this->store()->defer( 14 );
	}
}
