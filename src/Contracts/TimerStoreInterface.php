<?php
/**
 * Timer store contract.
 *
 * Persists the "next time we should show the review notice"
 * timestamp. The default WordPress-backed implementation stores it
 * as a site option; alternative implementations (in-memory, custom
 * tables, network-wide stores) can drop in for tests or for sites
 * that do not want one more `wp_options` row.
 *
 * The interface is deliberately split into a pure read
 * ({@see is_due}) and explicit lifecycle calls
 * ({@see start} and {@see defer}) so condition checks never have
 * side-effects on storage. The orchestrator seeds the schedule once
 * from its `register()` call; everything else just reads.
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
 * Interface TimerStoreInterface.
 */
interface TimerStoreInterface {

	/**
	 * Idempotently seed the show-time.
	 *
	 * Called once when the notice is registered. Implementations
	 * MUST NOT overwrite an existing schedule — that responsibility
	 * belongs to {@see defer}. This is what makes the call safe to
	 * place on every admin request.
	 *
	 * @since 2.0.0
	 *
	 * @param int $days Days from now to schedule the first show.
	 *
	 * @return void
	 */
	public function start( int $days ): void;

	/**
	 * Has the scheduled show-time been reached?
	 *
	 * Pure read — must not mutate persisted state. Returns false
	 * when nothing has been scheduled yet (i.e. {@see start} was
	 * never called).
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public function is_due(): bool;

	/**
	 * Push the next show-time further into the future.
	 *
	 * Called when the user picks "maybe later". Implementations
	 * should overwrite any existing timestamp.
	 *
	 * @since 2.0.0
	 *
	 * @param int $days Days from now to defer the next show.
	 *
	 * @return void
	 */
	public function defer( int $days ): void;
}
