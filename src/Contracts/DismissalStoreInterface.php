<?php
/**
 * Per-user dismissal store contract.
 *
 * Tracks whether the current user has dismissed the notice. The
 * default implementation uses `user_meta`, but consumers can swap
 * in a network-scoped or session-scoped store as needed.
 *
 * Note: this is intentionally per-user, not per-site — different
 * admins on the same install may want to dismiss independently.
 *
 * @link       https://duckdev.com/
 * @license    http://www.gnu.org/licenses/ GNU General Public License
 * @author     Joel James <me@joelsays.com>
 * @since      2.0.0
 * @package    Reviews
 * @subpackage Contracts
 */

namespace DuckDev\Reviews\Contracts;

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

/**
 * Interface DismissalStoreInterface.
 */
interface DismissalStoreInterface {

	/**
	 * Has the current user dismissed the notice already?
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public function is_dismissed(): bool;

	/**
	 * Record a dismissal for the current user.
	 *
	 * Idempotent — calling more than once for the same user is a
	 * no-op.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function dismiss(): void;
}
