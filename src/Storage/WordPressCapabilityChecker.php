<?php
/**
 * WordPress capability checker.
 *
 * Thin wrapper around `current_user_can()` — exists so that the
 * orchestrator depends on the interface, not the global function,
 * which keeps unit tests free of the `WP_User` / roles machinery.
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

use DuckDev\Reviews\Contracts\CapabilityCheckerInterface;

/**
 * Class WordPressCapabilityChecker.
 */
class WordPressCapabilityChecker implements CapabilityCheckerInterface {

	/**
	 * {@inheritDoc}
	 *
	 * @param string $capability Capability slug.
	 *
	 * @return bool
	 */
	public function can( string $capability ): bool {
		return current_user_can( $capability );
	}
}
