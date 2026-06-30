<?php
/**
 * Action router.
 *
 * Reads the action GET param the renderer adds to the "later" and
 * "dismiss" links, and routes it to the appropriate store. Lives in
 * its own class so the dispatch logic is testable without firing a
 * full WordPress request.
 *
 * Guards:
 *   - Only runs in admin requests (caller's responsibility — the
 *     orchestrator only hooks this on `admin_init`).
 *   - Requires the configured capability before touching state, so
 *     a stray GET param from a low-privilege user can't bump the
 *     timer for everyone on the site.
 *
 * @link       https://duckdev.com/
 * @license    http://www.gnu.org/licenses/ GNU General Public License
 * @author     Joel James <me@joelsays.com>
 * @since      2.0.0
 * @package    Reviews
 * @subpackage Support
 */

namespace DuckDev\Reviews\Support;

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

use DuckDev\Reviews\Contracts\CapabilityCheckerInterface;
use DuckDev\Reviews\Contracts\DismissalStoreInterface;
use DuckDev\Reviews\Contracts\ScreenResolverInterface;
use DuckDev\Reviews\Contracts\TimerStoreInterface;

/**
 * Class ActionRouter.
 */
class ActionRouter {

	/**
	 * Configuration container.
	 *
	 * @since 2.0.0
	 *
	 * @var Config
	 */
	private Config $config;

	/**
	 * Prefixer used to resolve the GET param name.
	 *
	 * @since 2.0.0
	 *
	 * @var KeyPrefixer
	 */
	private KeyPrefixer $prefixer;

	/**
	 * Timer store — receives `later` deferrals.
	 *
	 * @since 2.0.0
	 *
	 * @var TimerStoreInterface
	 */
	private TimerStoreInterface $timer;

	/**
	 * Dismissal store — receives `dismiss` flips.
	 *
	 * @since 2.0.0
	 *
	 * @var DismissalStoreInterface
	 */
	private DismissalStoreInterface $dismissal;

	/**
	 * Screen resolver — gate the action to the same screens the
	 * notice is allowed to render on.
	 *
	 * @since 2.0.0
	 *
	 * @var ScreenResolverInterface
	 */
	private ScreenResolverInterface $screen;

	/**
	 * Capability checker — gate the action to authorised users.
	 *
	 * @since 2.0.0
	 *
	 * @var CapabilityCheckerInterface
	 */
	private CapabilityCheckerInterface $capability;

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 *
	 * @param Config                     $config     Notice configuration.
	 * @param KeyPrefixer                $prefixer   Shared key prefixer.
	 * @param TimerStoreInterface        $timer      Timer store.
	 * @param DismissalStoreInterface    $dismissal  Dismissal store.
	 * @param ScreenResolverInterface    $screen     Current-screen resolver.
	 * @param CapabilityCheckerInterface $capability Capability checker.
	 */
	public function __construct(
		Config $config,
		KeyPrefixer $prefixer,
		TimerStoreInterface $timer,
		DismissalStoreInterface $dismissal,
		ScreenResolverInterface $screen,
		CapabilityCheckerInterface $capability
	) {
		$this->config     = $config;
		$this->prefixer   = $prefixer;
		$this->timer      = $timer;
		$this->dismissal  = $dismissal;
		$this->screen     = $screen;
		$this->capability = $capability;
	}

	/**
	 * Inspect the current request and run the requested action.
	 *
	 * Safe to call on every admin request — no-ops when there is no
	 * recognised action param.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function dispatch(): void {
		if ( ! $this->screen->is_allowed( $this->config->screens() ) ) {
			return;
		}

		if ( ! $this->capability->can( $this->config->capability() ) ) {
			return;
		}

		// Read directly from $_GET instead of filter_input() because
		// the latter snapshots the original request and is not
		// observable from unit tests. We sanitise with
		// `sanitize_key()` so the switch below only ever sees a
		// known shape, regardless of what arrives on the wire.
		$param  = $this->prefixer->key( 'action' );
		$action = isset( $_GET[ $param ] ) ? sanitize_key( wp_unslash( $_GET[ $param ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- side-effects are capability-gated above.

		switch ( $action ) {
			case 'later':
				// Match v1: push the next show-time out by twice the
				// original delay. Keeps regulars seeing the prompt
				// occasionally without nagging.
				$this->timer->defer( $this->config->days() * 2 );
				break;
			case 'dismiss':
				$this->dismissal->dismiss();
				break;
		}
	}
}
