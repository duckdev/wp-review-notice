<?php
/**
 * Default HTML renderer for the review notice.
 *
 * Produces the same markup v1 emitted from `Notice::render()` so
 * stylesheets and integration tests targeting the previous element
 * IDs / link structure keep working unchanged.
 *
 * Action links are built with `add_query_arg()` against the current
 * URL — clicking one re-loads the same admin page with the action
 * GET param appended, where {@see \DuckDev\Reviews\Support\ActionRouter}
 * picks it up.
 *
 * The message body is run through `wp_kses_post()` before output:
 * the bundled copy's `<strong>` tag (and any inline formatting a
 * consumer wants in a custom message) survives, but stray scripts
 * or unsafe attributes are stripped automatically. This is the
 * v2 safety upgrade over v1's raw `echo`.
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

use DuckDev\Reviews\Contracts\RendererInterface;
use DuckDev\Reviews\Support\Config;
use DuckDev\Reviews\Support\KeyPrefixer;

/**
 * Class DefaultRenderer.
 */
class DefaultRenderer implements RendererInterface {

	/**
	 * Message builder used to resolve the body copy.
	 *
	 * @since 2.0.0
	 *
	 * @var MessageBuilder
	 */
	private MessageBuilder $messages;

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 *
	 * @param MessageBuilder|null $messages Optional message builder.
	 *                                      Defaults to the bundled one.
	 */
	public function __construct( ?MessageBuilder $messages = null ) {
		$this->messages = $messages ?? new MessageBuilder();
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( Config $config, KeyPrefixer $prefixer ): void {
		$message = $this->messages->build( $config );

		// Bail silently on an empty message — rendering an empty
		// notice would still produce a visible (empty) admin notice
		// box, which is worse than not rendering at all.
		if ( '' === $message ) {
			return;
		}

		$labels     = $config->actionLabels();
		$action_key = $prefixer->key( 'action' );
		$review_url = 'https://wordpress.org/support/plugin/' . $config->slug() . '/reviews/#new-post';
		?>
		<div
			id="duckdev-reviews-<?php echo esc_attr( $config->slug() ); ?>"
			class="<?php echo esc_attr( $config->classes() ); ?>">
			<p>
				<?php echo wp_kses_post( $message ); ?>
			</p>
			<?php if ( ! empty( $labels['review'] ) ) : ?>
				<p>
					<a href="<?php echo esc_url( $review_url ); ?>" target="_blank">
						→ <?php echo esc_html( $labels['review'] ); ?>
					</a>
				</p>
			<?php endif; ?>
			<?php if ( ! empty( $labels['later'] ) ) : ?>
				<p>
					<a href="<?php echo esc_url( add_query_arg( $action_key, 'later' ) ); ?>">
						→ <?php echo esc_html( $labels['later'] ); ?>
					</a>
				</p>
			<?php endif; ?>
			<?php if ( ! empty( $labels['dismiss'] ) ) : ?>
				<p>
					<a href="<?php echo esc_url( add_query_arg( $action_key, 'dismiss' ) ); ?>">
						→ <?php echo esc_html( $labels['dismiss'] ); ?>
					</a>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}
}
