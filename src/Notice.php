<?php
/**
 * Review notice container / library entry point.
 *
 * Single class consumers interact with directly. It wires up:
 *
 *   KeyPrefixer (prefix + key naming)
 *     ├── SiteOptionTimerStore        (next show-time persistence)
 *     ├── UserMetaDismissalStore      (per-user dismissal flag)
 *     ├── AdminScreenResolver         (current screen check)
 *     ├── WordPressCapabilityChecker  (capability gate)
 *     ├── ActionRouter                (later / dismiss GET dispatch)
 *     └── DefaultRenderer + MessageBuilder (HTML output)
 *
 * Construction has no side effects beyond holding references — the
 * actual WordPress hooks are registered in {@see register()}. That
 * lets tests instantiate the container without polluting the global
 * hook system.
 *
 * Each container instance is scoped to a single plugin slug. Two
 * consumers using this library on the same site stay isolated
 * because their option keys, user meta keys, GET param names and
 * notice IDs are all derived from the prefix.
 *
 * Typical use:
 *
 *   \DuckDev\Reviews\Notice::create( 'my-plugin', 'My Plugin' )
 *       ->register();
 *
 * Consumers that need to swap a collaborator (custom storage, a
 * block-editor renderer, a stubbed capability checker for tests)
 * reach for the constructor directly.
 *
 * @link    https://github.com/duckdev/wp-review-notice
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author  Joel James <me@joelsays.com>
 * @since   2.0.0
 * @package Reviews
 */

namespace DuckDev\Reviews;

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

use DuckDev\Reviews\Contracts\CapabilityCheckerInterface;
use DuckDev\Reviews\Contracts\DismissalStoreInterface;
use DuckDev\Reviews\Contracts\RendererInterface;
use DuckDev\Reviews\Contracts\ScreenResolverInterface;
use DuckDev\Reviews\Contracts\TimerStoreInterface;
use DuckDev\Reviews\Rendering\DefaultRenderer;
use DuckDev\Reviews\Storage\AdminScreenResolver;
use DuckDev\Reviews\Storage\SiteOptionTimerStore;
use DuckDev\Reviews\Storage\UserMetaDismissalStore;
use DuckDev\Reviews\Storage\WordPressCapabilityChecker;
use DuckDev\Reviews\Support\ActionRouter;
use DuckDev\Reviews\Support\Config;
use DuckDev\Reviews\Support\KeyPrefixer;

/**
 * Class Notice.
 */
class Notice {

	/**
	 * Resolved configuration for this notice instance.
	 *
	 * @since 2.0.0
	 *
	 * @var Config
	 */
	private Config $config;

	/**
	 * Shared key prefixer.
	 *
	 * @since 2.0.0
	 *
	 * @var KeyPrefixer
	 */
	private KeyPrefixer $prefixer;

	/**
	 * Timer (show-time) store.
	 *
	 * @since 2.0.0
	 *
	 * @var TimerStoreInterface
	 */
	private TimerStoreInterface $timer;

	/**
	 * Per-user dismissal store.
	 *
	 * @since 2.0.0
	 *
	 * @var DismissalStoreInterface
	 */
	private DismissalStoreInterface $dismissal;

	/**
	 * Current-screen resolver.
	 *
	 * @since 2.0.0
	 *
	 * @var ScreenResolverInterface
	 */
	private ScreenResolverInterface $screen;

	/**
	 * Capability checker.
	 *
	 * @since 2.0.0
	 *
	 * @var CapabilityCheckerInterface
	 */
	private CapabilityCheckerInterface $capability;

	/**
	 * Notice renderer.
	 *
	 * @since 2.0.0
	 *
	 * @var RendererInterface
	 */
	private RendererInterface $renderer;

	/**
	 * Action router used by the admin_init hook.
	 *
	 * @since 2.0.0
	 *
	 * @var ActionRouter
	 */
	private ActionRouter $router;

	/**
	 * Constructor.
	 *
	 * All collaborators are optional — when omitted the library
	 * falls back to its bundled WordPress-backed implementations.
	 * This is the seam tests reach for: pass in-memory fakes here
	 * and the notice runs without touching the database.
	 *
	 * @since 2.0.0
	 *
	 * @param Config                          $config     Resolved configuration.
	 * @param TimerStoreInterface|null        $timer      Optional timer store.
	 * @param DismissalStoreInterface|null    $dismissal  Optional dismissal store.
	 * @param ScreenResolverInterface|null    $screen     Optional screen resolver.
	 * @param CapabilityCheckerInterface|null $capability Optional capability checker.
	 * @param RendererInterface|null          $renderer   Optional renderer.
	 */
	public function __construct(
		Config $config,
		?TimerStoreInterface $timer = null,
		?DismissalStoreInterface $dismissal = null,
		?ScreenResolverInterface $screen = null,
		?CapabilityCheckerInterface $capability = null,
		?RendererInterface $renderer = null
	) {
		$this->config     = $config;
		$this->prefixer   = new KeyPrefixer( $config->prefix() );
		$this->timer      = $timer ?? new SiteOptionTimerStore( $this->prefixer );
		$this->dismissal  = $dismissal ?? new UserMetaDismissalStore( $this->prefixer );
		$this->screen     = $screen ?? new AdminScreenResolver();
		$this->capability = $capability ?? new WordPressCapabilityChecker();
		$this->renderer   = $renderer ?? new DefaultRenderer();
		$this->router     = new ActionRouter(
			$this->config,
			$this->prefixer,
			$this->timer,
			$this->dismissal,
			$this->capability
		);
	}

	/**
	 * Convenience factory.
	 *
	 * Builds a Config from a loose options array and hands back a
	 * wired Notice. Consumers are expected to call
	 * {@see register()} themselves so the wiring decision (and the
	 * hook timing) stays explicit in their code.
	 *
	 * @since 2.0.0
	 *
	 * @param string               $slug    wp.org plugin slug.
	 * @param string               $name    Plugin display name.
	 * @param array<string, mixed> $options Optional overrides — see {@see Config::from_array()}.
	 *
	 * @return self
	 */
	public static function create( string $slug, string $name, array $options = array() ): self {
		return new self( Config::from_array( $slug, $name, $options ) );
	}

	/**
	 * Register the WordPress hooks that drive this notice.
	 *
	 * Idempotent across page loads — repeated registrations on the
	 * same request are a consumer bug, but harmless. The call also
	 * seeds the show-time schedule via the timer store so the very
	 * first eligible request can already see the notice.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function register(): void {
		// Seed the timer the moment the consumer wires the notice
		// up. The store implementation guarantees this is a no-op
		// when a schedule is already in place.
		$this->timer->start( $this->config->days() );

		// Dispatch later/dismiss before the notice hook runs so a
		// click takes effect on the same page-load.
		add_action( 'admin_init', array( $this->router, 'dispatch' ) );
		add_action( 'admin_notices', array( $this, 'render' ) );
		add_action( 'network_admin_notices', array( $this, 'render' ) );
	}

	/**
	 * Render the notice if all show-conditions are met.
	 *
	 * Conditions (all must be true):
	 *
	 *   1. Current screen is in the configured allow-list (or the
	 *      list is empty).
	 *   2. Current user has the configured capability.
	 *   3. The scheduled show-time has been reached.
	 *   4. The current user has not dismissed the notice.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! $this->can_show() ) {
			return;
		}

		$this->renderer->render( $this->config, $this->prefixer );
	}

	/**
	 * Aggregated show-condition check.
	 *
	 * Exposed publicly so consumers can drive their own rendering
	 * pipeline (e.g. AJAX) without re-implementing the gate logic.
	 *
	 * Note on ordering: cheaper checks run first so the common case
	 * (wrong screen) bails before we touch the database.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public function can_show(): bool {
		if ( ! $this->screen->is_allowed( $this->config->screens() ) ) {
			return false;
		}

		if ( ! $this->capability->can( $this->config->capability() ) ) {
			return false;
		}

		if ( ! $this->timer->is_due() ) {
			return false;
		}

		return ! $this->dismissal->is_dismissed();
	}

	/**
	 * Access the resolved configuration.
	 *
	 * @since 2.0.0
	 *
	 * @return Config
	 */
	public function config(): Config {
		return $this->config;
	}

	/**
	 * Access the shared key prefixer.
	 *
	 * @since 2.0.0
	 *
	 * @return KeyPrefixer
	 */
	public function prefixer(): KeyPrefixer {
		return $this->prefixer;
	}
}
