<?php
/**
 * Notice renderer contract.
 *
 * The renderer turns a {@see \DuckDev\Reviews\Support\Config} into
 * HTML on the admin page. Splitting it out from the orchestrator
 * means a consumer can supply a completely custom template — block
 * editor banner, dashboard widget, a snackbar, anything — without
 * forking the library.
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

use DuckDev\Reviews\Support\Config;
use DuckDev\Reviews\Support\KeyPrefixer;

/**
 * Interface RendererInterface.
 */
interface RendererInterface {

	/**
	 * Echo the notice HTML.
	 *
	 * The renderer receives both the config (for copy + labels +
	 * classes) and the prefixer (to build the action URLs with the
	 * correct GET param name).
	 *
	 * @since 2.0.0
	 *
	 * @param Config      $config   Resolved notice configuration.
	 * @param KeyPrefixer $prefixer Key prefixer used for the action GET param.
	 *
	 * @return void
	 */
	public function render( Config $config, KeyPrefixer $prefixer ): void;
}
