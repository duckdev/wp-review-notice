<?php
/**
 * Capability checker contract.
 *
 * Wraps `current_user_can()` so capability rules can be stubbed in
 * tests without going through the WordPress user/role machinery.
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
 * Interface CapabilityCheckerInterface.
 */
interface CapabilityCheckerInterface {

	/**
	 * Does the current user have the given capability?
	 *
	 * @since 2.0.0
	 *
	 * @param string $capability Capability slug, e.g. `manage_options`.
	 *
	 * @return bool
	 */
	public function can( string $capability ): bool;
}
