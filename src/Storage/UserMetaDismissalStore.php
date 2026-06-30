<?php
/**
 * User-meta backed dismissal store.
 *
 * Persists the per-user "I already dismissed this notice" flag in
 * `user_meta`. Matches v1 exactly so existing dismissals carry over
 * after the upgrade — same meta key, same truthy value.
 *
 * Why user meta and not a site option? Two admins on the same site
 * should be able to dismiss the prompt independently; a site-wide
 * dismissal would silence the notice for the user that *didn't*
 * click "I already did" just because their colleague did.
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

use DuckDev\Reviews\Contracts\DismissalStoreInterface;
use DuckDev\Reviews\Support\KeyPrefixer;

/**
 * Class UserMetaDismissalStore.
 */
class UserMetaDismissalStore implements DismissalStoreInterface {

	/**
	 * Prefixer used to name the meta key.
	 *
	 * @since 2.0.0
	 *
	 * @var KeyPrefixer
	 */
	private KeyPrefixer $prefixer;

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 *
	 * @param KeyPrefixer $prefixer Shared key prefixer.
	 */
	public function __construct( KeyPrefixer $prefixer ) {
		$this->prefixer = $prefixer;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isDismissed(): bool {
		$user_id = get_current_user_id();

		// Logged-out requests can never have a dismissal recorded —
		// short-circuit before touching the meta table.
		if ( 0 === $user_id ) {
			return false;
		}

		return (bool) get_user_meta( $user_id, $this->prefixer->key( 'dismissed' ), true );
	}

	/**
	 * {@inheritDoc}
	 */
	public function dismiss(): void {
		$user_id = get_current_user_id();

		if ( 0 === $user_id ) {
			return;
		}

		update_user_meta( $user_id, $this->prefixer->key( 'dismissed' ), true );
	}
}
