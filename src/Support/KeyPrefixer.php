<?php
/**
 * Key prefixing helper.
 *
 * Every option, user meta key and GET parameter the library reads or
 * writes is namespaced through this helper. Doing it in one place
 * means:
 *
 *   - Two plugins consuming this library on the same site never
 *     collide on `*_review_time` or `*_review_dismissed`.
 *   - Keys carry a reserved `_review_` segment so they never clash
 *     with unrelated plugin options that happen to share the same
 *     consumer prefix (e.g. `loggedin_settings`).
 *   - Storage drivers stay storage-agnostic: they receive a prefixer
 *     and ask it for the final key, instead of hard-coding the
 *     reserved `_review_` segment themselves.
 *
 * Pure value object — no WordPress calls — so it's trivial to unit
 * test in isolation.
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
 * Class KeyPrefixer.
 */
class KeyPrefixer {

	/**
	 * Consumer-supplied prefix.
	 *
	 * Typically derived from the plugin's wp.org slug with dashes
	 * normalised to underscores (e.g. "my-plugin" → "my_plugin"),
	 * but consumers can override it explicitly.
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	private string $prefix;

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 *
	 * @param string $prefix Non-empty prefix shared by every key the library writes.
	 *
	 * @throws NoticeException When $prefix is empty after trimming. An empty
	 *                        prefix would let two unrelated plugins read and
	 *                        overwrite each other's review schedule, so we
	 *                        refuse to construct in that case.
	 */
	public function __construct( string $prefix ) {
		$prefix = trim( $prefix );

		if ( '' === $prefix ) {
			throw new NoticeException( 'Review notice prefix must be a non-empty string.' );
		}

		$this->prefix = $prefix;
	}

	/**
	 * Get the raw consumer prefix.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function prefix(): string {
		return $this->prefix;
	}

	/**
	 * Build a fully-qualified key.
	 *
	 * Format: `{prefix}_review_{name}`. The `_review_` segment is
	 * reserved so consumer prefixes (typically the plugin slug) can
	 * be reused for the plugin's own options without colliding with
	 * anything the library writes.
	 *
	 * @since 2.0.0
	 *
	 * @param string $name Caller-supplied key name (e.g. "time", "dismissed", "action").
	 *
	 * @return string
	 */
	public function key( string $name ): string {
		return $this->prefix . '_review_' . $name;
	}
}
