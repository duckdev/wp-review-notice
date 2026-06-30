<?php
/**
 * Current-screen resolver contract.
 *
 * Wraps WordPress's `get_current_screen()` so the notice's
 * "should I show on this admin page?" check can be unit-tested
 * without bootstrapping a real screen.
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
 * Interface ScreenResolverInterface.
 */
interface ScreenResolverInterface {

	/**
	 * Is the current screen one of the allowed ones?
	 *
	 * An empty allow-list means "every admin screen is allowed" —
	 * preserved from v1 for backwards compatibility, even though
	 * it's not recommended for new consumers.
	 *
	 * @since 2.0.0
	 *
	 * @param array<int, string> $allowed Allow-list of screen IDs.
	 *
	 * @return bool
	 */
	public function is_allowed( array $allowed ): bool;
}
