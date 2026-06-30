<?php
/**
 * Default message builder.
 *
 * Generates the "Hey {user}, you've been using {plugin} for…" copy
 * when the consumer didn't pass a custom message. Lives in its own
 * class so consumers can replace the copy strategy (e.g. localised
 * variants, A/B variations) by swapping a single collaborator on
 * the Notice constructor.
 *
 * The bundled `duckdev_reviews_notice_message` filter is preserved
 * so existing customisations on consumer sites keep firing.
 *
 * @link       https://duckdev.com/
 * @license    http://www.gnu.org/licenses/ GNU General Public License
 * @author     Joel James <me@joelsays.com>
 * @since      2.0.0
 * @package    Reviews
 * @subpackage Rendering
 */

namespace DuckDev\Reviews\Rendering;

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

use DuckDev\Reviews\Support\Config;

/**
 * Class MessageBuilder.
 */
class MessageBuilder {

	/**
	 * Resolve the message text for a notice.
	 *
	 * Honors the precomputed message on the config when set; falls
	 * back to the bundled copy otherwise. Either way the result is
	 * passed through the `duckdev_reviews_notice_message` filter so
	 * downstream customisations always run.
	 *
	 * @since 2.0.0
	 *
	 * @param Config $config Resolved notice configuration.
	 *
	 * @return string HTML message — the renderer runs it through
	 *                `wp_kses_post()` before output.
	 */
	public function build( Config $config ): string {
		$message = '' !== $config->message()
			? $config->message()
			: $this->default_message( $config );

		/**
		 * Filter to modify review notice message.
		 *
		 * @param string $message Notice message.
		 * @param int    $days    Days to show review.
		 *
		 * @since 1.0.2
		 */
		return (string) apply_filters( 'duckdev_reviews_notice_message', $message, $config->days() );
	}

	/**
	 * Build the default bundled copy.
	 *
	 * Pulled out so consumers subclassing or wrapping this class
	 * can reuse the default and only tweak around it.
	 *
	 * Strings are translated against WordPress core's `default`
	 * domain — the library can't ship translations for every
	 * consumer locale, and the `duckdev_reviews_notice_message`
	 * filter is the supported way to replace the copy entirely.
	 *
	 * @since 2.0.0
	 *
	 * @param Config $config Resolved notice configuration.
	 *
	 * @return string
	 */
	protected function default_message( Config $config ): string {
		$current_user = wp_get_current_user();
		$display_name = isset( $current_user->display_name ) ? (string) $current_user->display_name : '';

		// Friendly fallback when the user has no display name set —
		// "Hey , I noticed…" looks broken in the wild.
		$name = '' === $display_name
			? __( 'friend', 'default' )
			: ucwords( $display_name );

		return sprintf(
			// translators: %1$s Current user's name, %2$s Plugin name, %3$d Days.
			esc_html__(
				'Hey %1$s, I noticed you\'ve been using %2$s for more than %3$d days – that’s awesome! Could you please do me a BIG favor and give it a 5-star rating on WordPress? Just to help us spread the word and boost our motivation.',
				'default'
			),
			esc_html( $name ),
			'<strong>' . esc_html( $config->name() ) . '</strong>',
			$config->days()
		);
	}
}
