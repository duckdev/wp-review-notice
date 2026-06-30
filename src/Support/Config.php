<?php
/**
 * Notice configuration value object.
 *
 * Captures every consumer-tunable setting in one immutable container
 * so the rest of the library can pass a single dependency around
 * instead of threading a dozen parameters. Defaults match v1
 * behaviour exactly:
 *
 *   - 7 day delay before the first show.
 *   - `manage_options` capability gate.
 *   - All admin screens (empty allow-list).
 *   - Bundled message and action labels in the `duckdev` text domain.
 *
 * Construction is funnelled through {@see Config::fromArray()} so the
 * public {@see \DuckDev\Reviews\Notice::get()} entry point can keep
 * accepting the same loose array shape v1 used.
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
	 * wp.org plugin slug — drives the review URL and the default prefix.
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
	 * An empty array means "show on every admin screen" (matches v1,
	 * but not recommended — most consumers should constrain this).
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
	 * Text domain used for the bundled strings.
	 *
	 * Consumers passing a fully formatted `message` can ignore this;
	 * it only matters when the library generates its own copy.
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	private string $domain;

	/**
	 * Pre-built notice message.
	 *
	 * Empty means "let the library build one from {@see name} and
	 * {@see days}". The string is rendered without escaping — matching
	 * v1 — so consumers passing custom HTML are responsible for
	 * sanitising it themselves.
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
	 * are ignored by the renderer.
	 *
	 * @since 2.0.0
	 *
	 * @var array<string, string>
	 */
	private array $action_labels;

	/**
	 * Key prefix used for option / user-meta / GET param namespacing.
	 *
	 * Defaults to the slug with dashes converted to underscores so the
	 * v1 storage layout is preserved.
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	private string $prefix;

	/**
	 * Private constructor — go through {@see fromArray()}.
	 *
	 * Keeping the constructor private avoids two different call sites
	 * having to repeat the defaulting logic, and lets us evolve the
	 * option shape without breaking existing callers.
	 *
	 * @since 2.0.0
	 *
	 * @param string                $slug          wp.org plugin slug.
	 * @param string                $name          Plugin display name.
	 * @param int                   $days          Days before first show.
	 * @param array<int, string>    $screens       Allowed admin screen IDs.
	 * @param string                $cap           Required capability.
	 * @param array<int, string>    $classes       Extra CSS classes.
	 * @param string                $domain        Text domain.
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
		string $domain,
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
		$this->domain        = $domain;
		$this->message       = $message;
		$this->action_labels = $action_labels;
		$this->prefix        = $prefix;
	}

	/**
	 * Build a config from the loose options array v1 accepts.
	 *
	 * Mirrors `Notice::setup()` from v1 — same keys, same defaults,
	 * same defaulting rules — so consumers can migrate without
	 * touching their call site.
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
	 *     @type string                $domain        Text domain. Default 'duckdev'.
	 *     @type string                $message       Custom message. Default empty (auto-generated).
	 *     @type array<string, string> $action_labels Override action labels.
	 *     @type string                $prefix        Storage prefix. Defaults to slug with `-` → `_`.
	 * }
	 *
	 * @return self
	 *
	 * @throws NoticeException When $slug or $name is empty. The notice is
	 *                        useless without both, and silently no-op'ing
	 *                        (v1's behaviour) tends to hide consumer bugs.
	 */
	public static function fromArray( string $slug, string $name, array $options = array() ): self {
		$slug = trim( $slug );
		$name = trim( $name );

		if ( '' === $slug || '' === $name ) {
			throw new NoticeException( 'Review notice requires a non-empty slug and name.' );
		}

		// Merge top-level options against the v1 defaults.
		$options = array_merge(
			array(
				'days'          => 7,
				'screens'       => array(),
				'cap'           => 'manage_options',
				'classes'       => array(),
				'domain'        => 'duckdev',
				'message'       => '',
				'action_labels' => array(),
				'prefix'        => '',
			),
			$options
		);

		$domain = (string) $options['domain'];

		// Action labels are merged independently so consumers can
		// override one without re-stating the others.
		$action_labels = array_merge(
			array(
				'review'  => esc_html__( 'Ok, you deserve it', 'default' ),
				'later'   => esc_html__( 'Nope, maybe later', 'default' ),
				'dismiss' => esc_html__( 'I already did', 'default' ),
			),
			(array) $options['action_labels']
		);

		// Default the prefix to the slug with `-` → `_` so it forms
		// a valid option-name fragment. Consumers running multiple
		// notices in one plugin override `prefix` to scope them.
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
			$domain,
			(string) $options['message'],
			$action_labels,
			$prefix
		);
	}

	/**
	 * @since 2.0.0
	 * @return string
	 */
	public function slug(): string {
		return $this->slug;
	}

	/**
	 * @since 2.0.0
	 * @return string
	 */
	public function name(): string {
		return $this->name;
	}

	/**
	 * @since 2.0.0
	 * @return int
	 */
	public function days(): int {
		return $this->days;
	}

	/**
	 * @since 2.0.0
	 * @return array<int, string>
	 */
	public function screens(): array {
		return $this->screens;
	}

	/**
	 * @since 2.0.0
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
	 * @since 2.0.0
	 * @return string
	 */
	public function domain(): string {
		return $this->domain;
	}

	/**
	 * @since 2.0.0
	 * @return string
	 */
	public function message(): string {
		return $this->message;
	}

	/**
	 * @since 2.0.0
	 *
	 * @return array<string, string>
	 */
	public function actionLabels(): array {
		return $this->action_labels;
	}

	/**
	 * @since 2.0.0
	 * @return string
	 */
	public function prefix(): string {
		return $this->prefix;
	}
}
