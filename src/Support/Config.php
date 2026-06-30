<?php
/**
 * Notice configuration value object.
 *
 * Captures every consumer-tunable setting in one immutable container
 * so the rest of the library can pass a single dependency around
 * instead of threading a dozen parameters.
 *
 * Defaults:
 *
 *   - 7 day delay before the first show.
 *   - `manage_options` capability gate.
 *   - All admin screens (empty allow-list).
 *   - Bundled English copy with the "Ok / Later / Dismiss" actions.
 *
 * Construction is funnelled through {@see Config::from_array()} so
 * consumers can keep using the loose array shape that's idiomatic
 * for WordPress plugin APIs while the rest of the library benefits
 * from a typed value object internally.
 *
 * The object is intentionally immutable: once a notice is wired up,
 * its config should not mutate underneath the collaborators that
 * captured a reference to it.
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

use DuckDev\Reviews\Exceptions\NoticeException;

/**
 * Class Config.
 */
class Config {

	/**
	 * WordPress.org plugin slug — drives the review URL and the default prefix.
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	private string $slug;

	/**
	 * Human-readable plugin name shown inside the notice copy.
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	private string $name;

	/**
	 * Days to wait after first activation before the notice appears.
	 *
	 * @since 2.0.0
	 *
	 * @var int
	 */
	private int $days;

	/**
	 * Allow-list of admin screen IDs.
	 *
	 * An empty array means "show on every admin screen" — supported
	 * but not recommended; most consumers should constrain this to
	 * the screens their plugin actually owns.
	 *
	 * @since 2.0.0
	 *
	 * @var array<int, string>
	 */
	private array $screens;

	/**
	 * Capability required to see and act on the notice.
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	private string $cap;

	/**
	 * Additional CSS classes appended to the default
	 * `notice notice-info` wrapper.
	 *
	 * @since 2.0.0
	 *
	 * @var array<int, string>
	 */
	private array $classes;

	/**
	 * Pre-built notice message.
	 *
	 * Empty means "let the library build one from {@see name} and
	 * {@see days}". The renderer passes the result through
	 * `wp_kses_post()` before output, so consumers passing inline
	 * HTML (links, strong tags, etc.) are safe by default.
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	private string $message;

	/**
	 * Action link labels keyed by action name.
	 *
	 * Recognised keys: `review`, `later`, `dismiss`. Any other keys
	 * are ignored by the renderer. Set a label to '' to hide that
	 * action entirely.
	 *
	 * @since 2.0.0
	 *
	 * @var array<string, string>
	 */
	private array $action_labels;

	/**
	 * Key prefix used for option / user-meta / GET param namespacing.
	 *
	 * Defaults to the slug with dashes converted to underscores so
	 * it forms a valid option-name fragment. Consumers running
	 * multiple notices in one plugin override `prefix` to scope
	 * them.
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	private string $prefix;

	/**
	 * Private constructor — go through {@see from_array()}.
	 *
	 * Keeping the constructor private avoids two different call
	 * sites having to repeat the defaulting logic, and lets us
	 * evolve the option shape without breaking existing callers.
	 *
	 * @since 2.0.0
	 *
	 * @param string                $slug          wp.org plugin slug.
	 * @param string                $name          Plugin display name.
	 * @param int                   $days          Days before first show.
	 * @param array<int, string>    $screens       Allowed admin screen IDs.
	 * @param string                $cap           Required capability.
	 * @param array<int, string>    $classes       Extra CSS classes.
	 * @param string                $message       Pre-built message (empty to auto-generate).
	 * @param array<string, string> $action_labels Action button labels.
	 * @param string                $prefix        Storage prefix.
	 */
	private function __construct(
		string $slug,
		string $name,
		int $days,
		array $screens,
		string $cap,
		array $classes,
		string $message,
		array $action_labels,
		string $prefix
	) {
		$this->slug          = $slug;
		$this->name          = $name;
		$this->days          = $days;
		$this->screens       = $screens;
		$this->cap           = $cap;
		$this->classes       = $classes;
		$this->message       = $message;
		$this->action_labels = $action_labels;
		$this->prefix        = $prefix;
	}

	/**
	 * Build a config from a loose options array.
	 *
	 * This is the documented entry point for the constructor —
	 * `Notice::create()` uses it under the hood, but consumers that
	 * want to inject custom collaborators construct the Config
	 * themselves and then pass it straight to the Notice
	 * constructor.
	 *
	 * @since 2.0.0
	 *
	 * @param string               $slug    wp.org plugin slug. Required.
	 * @param string               $name    Plugin display name. Required.
	 * @param array<string, mixed> $options {
	 *     Optional overrides.
	 *
	 *     @type int                   $days          Days before first show. Default 7.
	 *     @type array<int, string>    $screens       Allowed screen IDs. Default empty (all).
	 *     @type string                $cap           Required capability. Default 'manage_options'.
	 *     @type array<int, string>    $classes       Extra CSS classes. Default empty.
	 *     @type string                $message       Custom message. Default empty (auto-generated).
	 *     @type array<string, string> $action_labels Override action labels.
	 *     @type string                $prefix        Storage prefix. Defaults to slug with `-` → `_`.
	 * }
	 *
	 * @return self
	 *
	 * @throws NoticeException When $slug or $name is empty.
	 */
	public static function from_array( string $slug, string $name, array $options = array() ): self {
		$slug = trim( $slug );
		$name = trim( $name );

		if ( '' === $slug || '' === $name ) {
			throw new NoticeException( 'Review notice requires a non-empty slug and name.' );
		}

		$options = array_merge(
			array(
				'days'          => 7,
				'screens'       => array(),
				'cap'           => 'manage_options',
				'classes'       => array(),
				'message'       => '',
				'action_labels' => array(),
				'prefix'        => '',
			),
			$options
		);

		// Action labels are merged independently so consumers can
		// override one without re-stating the others. Strings are
		// translated against WordPress core's `default` domain
		// because libraries can't ship translations for every
		// consumer locale — wanting custom copy means supplying
		// `action_labels` (or `message`) directly.
		$action_labels = array_merge(
			array(
				'review'  => esc_html__( 'Ok, you deserve it', 'default' ),
				'later'   => esc_html__( 'Nope, maybe later', 'default' ),
				'dismiss' => esc_html__( 'I already did', 'default' ),
			),
			(array) $options['action_labels']
		);

		$prefix = '' === (string) $options['prefix']
			? str_replace( '-', '_', $slug )
			: (string) $options['prefix'];

		return new self(
			$slug,
			$name,
			(int) $options['days'],
			array_values( (array) $options['screens'] ),
			(string) $options['cap'],
			array_values( (array) $options['classes'] ),
			(string) $options['message'],
			$action_labels,
			$prefix
		);
	}

	/**
	 * Get the wp.org plugin slug.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function slug(): string {
		return $this->slug;
	}

	/**
	 * Get the human-readable plugin name.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function name(): string {
		return $this->name;
	}

	/**
	 * Get the delay (in days) before the first show.
	 *
	 * @since 2.0.0
	 *
	 * @return int
	 */
	public function days(): int {
		return $this->days;
	}

	/**
	 * Get the allow-list of admin screen IDs.
	 *
	 * @since 2.0.0
	 *
	 * @return array<int, string>
	 */
	public function screens(): array {
		return $this->screens;
	}

	/**
	 * Get the required capability.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function capability(): string {
		return $this->cap;
	}

	/**
	 * Resolve the final CSS class list for the notice wrapper.
	 *
	 * The base `notice notice-info` pair is always present so the
	 * notice picks up WordPress core styling even when the consumer
	 * forgets to pass any classes.
	 *
	 * @since 2.0.0
	 *
	 * @return string Space-separated class names.
	 */
	public function classes(): string {
		$classes = array_unique(
			array_merge( array( 'notice', 'notice-info' ), $this->classes )
		);

		return implode( ' ', $classes );
	}

	/**
	 * Get the pre-built message (empty if auto-generated).
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function message(): string {
		return $this->message;
	}

	/**
	 * Get the action link labels.
	 *
	 * @since 2.0.0
	 *
	 * @return array<string, string>
	 */
	public function action_labels(): array {
		return $this->action_labels;
	}

	/**
	 * Get the storage key prefix.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function prefix(): string {
		return $this->prefix;
	}
}
