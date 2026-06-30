<?php
/**
 * Site-option backed timer store.
 *
 * Persists the "next show-time" timestamp in the `*_options` table
 * via `get_site_option()` / `update_site_option()`. On multisite
 * the schedule is shared network-wide, which is what plugin
 * authors normally expect: one review prompt per network admin
 * rather than one per blog.
 *
 * The store implements the v2 split contract:
 *
 *   - {@see start} is idempotent — only writes when nothing is
 *     scheduled yet. Called once from the orchestrator's
 *     `register()`.
 *   - {@see isDue} is a pure read. `canShow()` can call it on every
 *     request without mutating state.
 *   - {@see defer} unconditionally overwrites; that's the explicit
 *     "later" action.
 *
 * @link       https://duckdev.com/
 * @license    http://www.gnu.org/licenses/ GNU General Public License
 * @author     Joel James <me@joelsays.com>
 * @since      2.0.0
 * @package    Reviews
 * @subpackage Storage
 */

namespace DuckDev\Reviews\Storage;

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

use DuckDev\Reviews\Contracts\TimerStoreInterface;
use DuckDev\Reviews\Support\KeyPrefixer;

/**
 * Class SiteOptionTimerStore.
 */
class SiteOptionTimerStore implements TimerStoreInterface {

	/**
	 * Prefixer used to name the option row.
	 *
	 * @since 2.0.0
	 *
	 * @var KeyPrefixer
	 */
	private KeyPrefixer $prefixer;

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 *
	 * @param KeyPrefixer $prefixer Shared key prefixer.
	 */
	public function __construct( KeyPrefixer $prefixer ) {
		$this->prefixer = $prefixer;
	}

	/**
	 * {@inheritDoc}
	 *
	 * Reads the existing schedule first; only writes when nothing
	 * is stored. That guarantee is what lets the orchestrator call
	 * this on every admin request from `register()` without
	 * resetting the clock for users who already have a schedule.
	 */
	public function start( int $days ): void {
		$key = $this->prefixer->key( 'time' );

		if ( ! empty( get_site_option( $key ) ) ) {
			return;
		}

		update_site_option( $key, time() + ( $days * DAY_IN_SECONDS ) );
	}

	/**
	 * {@inheritDoc}
	 */
	public function isDue(): bool {
		$time = get_site_option( $this->prefixer->key( 'time' ) );

		// Unseeded schedule → not due. `start()` is expected to run
		// before `isDue()` in normal lifecycle; returning false here
		// is the safe default if a consumer reaches for `canShow()`
		// before registering.
		if ( empty( $time ) ) {
			return false;
		}

		return (int) $time <= time();
	}

	/**
	 * {@inheritDoc}
	 */
	public function defer( int $days ): void {
		update_site_option(
			$this->prefixer->key( 'time' ),
			time() + ( $days * DAY_IN_SECONDS )
		);
	}
}
