<?php

declare( strict_types=1 );

namespace DuckDev\Reviews\Tests\Storage;

use Brain\Monkey\Functions;
use DuckDev\Reviews\Storage\UserMetaDismissalStore;
use DuckDev\Reviews\Support\KeyPrefixer;
use DuckDev\Reviews\Tests\TestCase;

final class UserMetaDismissalStoreTest extends TestCase {

	private function store(): UserMetaDismissalStore {
		return new UserMetaDismissalStore( new KeyPrefixer( 'p' ) );
	}

	public function test_is_dismissed_false_for_logged_out_users(): void {
		Functions\expect( 'get_current_user_id' )->once()->andReturn( 0 );
		Functions\expect( 'get_user_meta' )->never();

		$this->assertFalse( $this->store()->is_dismissed() );
	}

	public function test_is_dismissed_reads_user_meta(): void {
		Functions\expect( 'get_current_user_id' )->once()->andReturn( 5 );
		Functions\expect( 'get_user_meta' )->once()->with( 5, 'p_review_dismissed', true )->andReturn( '1' );

		$this->assertTrue( $this->store()->is_dismissed() );
	}

	public function test_dismiss_writes_user_meta(): void {
		Functions\expect( 'get_current_user_id' )->once()->andReturn( 5 );
		Functions\expect( 'update_user_meta' )->once()->with( 5, 'p_review_dismissed', true )->andReturn( true );

		$this->store()->dismiss();
	}

	public function test_dismiss_skipped_for_logged_out_users(): void {
		Functions\expect( 'get_current_user_id' )->once()->andReturn( 0 );
		Functions\expect( 'update_user_meta' )->never();

		$this->store()->dismiss();
	}
}
