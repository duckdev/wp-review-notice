<?php
/**
 * Helper library to as for a wp.org review.
 *
 * Review notice will be shown using WordPress admin notices after
 * a specified time of plugin/theme use.
 * This is mainly developed to reuse on my plugins but anyone can
 * use it as a library.
 *
 * @author     Joel James <me@joelsays.com>
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @copyright  Copyright (c) 2021, Joel James
 * @link       https://github.com/duckdev/wp-review-notice/
 * @package    DuckDev
 * @subpackage Pages
 */

namespace DuckDev\Reviews;

// Should be called only by WordPress.
defined( 'WPINC' ) || die;

/**
 * Class Notice.
 *
 * Main class that handles review notice.
 *
 * @package DuckDev\Reviews
 */
class Notice {

	/**
	 * Prefix for all options and keys.
	 *
	 * Override only when required.
	 *
	 * @var string $prefix
	 *
	 * @since 1.0.0
	 */
	private $prefix = '';

	/**
	 * Plugin name to show in review.
	 *
	 * @var string $name
	 *
	 * @since 1.0.0
	 */
	private $name = '';

	/**
	 * Plugin slug in https://wordpress.org/plugins/{slug}.
	 *
	 * @var string $slug
	 *
	 * @since 1.0.0
	 */
	private $slug = '';

	/**
	 * Minimum no. of days to show the notice after.
	 *
	 * Currently we support only days.
	 *
	 * @var int $days
	 *
	 * @since 1.0.0
	 */
	private $days = 7;

	/**
	 * WP admin page screen IOs to show notice in.
	 *
	 * If it's empty, we will show it on all pages.
	 *
	 * @var array $screens
	 *
	 * @since 1.0.0
	 */
	private $screens = array();

	/**
	 * Notice classes to set additional classes.
	 *
	 * By default we use WP info notice class.
	 *
	 * @var array $classes
	 *
	 * @since 1.0.0
	 */
	private $classes = array( 'notice', 'notice-info' );

	/**
	 * Actions link texts.
	 *
	 * Adding extra items to the array will not do anything.
	 *
	 * @var array $actions
	 *
	 * @since 1.0.2
	 */
	private $action_labels = array();

	/**
	 * Main message of the notice.
	 *
	 * @var string $message
	 *
	 * @since 1.0.2
	 */
	private $message = '';

	/**
	 * Minimum capability for the user to see and dismiss notice.
	 *
	 * @see   https://wordpress.org/support/article/roles-and-capabilities/
	 *
	 * @var string $cap
	 *
	 * @since 1.0.0
	 */
	private $cap = 'manage_options';

	/**
	 * Text domain for translations.
	 *
	 * @var string $domain
	 *
	 * @since 1.0.0
	 */
	private $domain = '';

	/**
	 * Create new notice instance with provided options.
	 *
	 * Do not use any hooks to run these functions because
	 * we don't know in which hook and priority everyone is
	 * going to initialize this notice.
	 *
	 * @param string $slug    WP.org slug for plugin.
	 * @param string $name    Name of plugin.
	 * @param array  $options Array of options (@see Notice::get()).
	 *
	 * @since  4.0.0
	 * @access private
	 *
	 * @return void
	 */
	private function __construct( $slug, $name, array $options ) {
		// Only for admin side.
		if ( is_admin() ) {
			// Setup options.
			$this->setup( $slug, $name, $options );

			// Process actions.
			$this->actions();
		}
	}

	/**
	 * Create and get new notice instance.
	 *
	 * Use this to setup new plugin notice to avoid multiple instances
	 * of same plugin notice.
	 * If you provide wrong slug, please note we will still link to the
	 * wrong wp.org plugin page for reviews.
	 *
	 * @param string $slug    WP.org slug for plugin.
	 * @param string $name    Name of plugin.
	 * @param array  $options {
	 *                        Array of options.
	 *
	 * @type int     $days    No. of days after the notice is shown.
	 * @type array   $screens WP screen IDs to show notice.
	 *                        Leave empty to show in all pages (not recommended).
	 * @type string  $cap     User capability to show notice.
	 *                        Make sure to use proper capability for multisite.
	 * @type array   $classes Additional class names for notice.
	 * @type string  $domain  Text domain for translations.
	 * @type string  $prefix  To override default option prefix.
	 * }
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return Notice
	 */
	public static function get( $slug, $name, array $options ) {
		static $notices = array();

		// Create new instance if not already created.
		if ( ! isset( $notices[ $slug ] ) || ! $notices[ $slug ] instanceof Notice ) {
			$notices[ $slug ] = new self( $slug, $name, $options );
		}

		return $notices[ $slug ];
	}

	/**
	 * Render the review notice.
	 *
	 * Review notice will be rendered only if all these conditions met:
	 * > Current screen is an allowed screen (@since  1.0.0
	 *
	 * @access public
	 *
	 * @see    Noticee::is_capable())
	 * > It's time to show the notice (@see Noticee::is_time())
	 * > User has not dismissed the notice (@see Noticee::is_dismissed())
	 *
	 * @see    Noticee::in_screen())
	 * > Current user has the required capability (@return void
	 */
	public function render() {
		// Check conditions.
		if ( ! $this->can_show() && ! empty( $this->message ) ) {
			return;
		}
		?>

		<div
			id="duckdev-reviews-<?php echo esc_attr( $this->slug ); ?>"
			class="<?php echo esc_attr( $this->get_classes() ); ?>">
			<p>
				<?php echo $this->message; ?>
			</p>
			<?php if ( ! empty( $this->action_labels['review'] ) ) : ?>
				<p>
					<a href="https://wordpress.org/support/plugin/<?php echo esc_html( $this->slug ); ?>/reviews/#new-post" target="_blank">
						→ <?php echo $this->action_labels['review']; ?>
					</a>
				</p>
			<?php endif; ?>
			<?php if ( ! empty( $this->action_labels['later'] ) ) : ?>
				<p>
					<a href="<?php echo esc_url( add_query_arg( $this->key( 'action' ), 'later' ) ); ?>">
						→ <?php echo $this->action_labels['later']; ?>
					</a>
				</p>
			<?php endif; ?>
			<?php if ( ! empty( $this->action_labels['dismiss'] ) ) : ?>
				<p>
					<a href="<?php echo esc_url( add_query_arg( $this->key( 'action' ), 'dismiss' ) ); ?>">
						→ <?php echo $this->action_labels['dismiss']; ?>
					</a>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Check if it's time to show the notice.
	 *
	 * Based on the day provided, we will check if the current
	 * timestamp exceeded or reached the notice time.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @uses   get_site_option()
	 * @uses   update_site_option()
	 *
	 * @return bool
	 */
	protected function is_time() {
		// Get the notice time.
		$time = get_site_option( $this->key( 'time' ) );

		// If not set, set now and bail.
		if ( empty( $time ) ) {
			$time = time() + ( $this->days * DAY_IN_SECONDS );
			// Set to future.
			update_site_option( $this->key( 'time' ), $time );

			return true;
		}

		// Check if time passed or reached.
		return (int) $time <= time();
	}

	/**
	 * Check if the notice is already dismissed.
	 *
	 * If a user has dismissed the notice, do not show
	 * notice to the current user again.
	 * We store the flag in current user's meta data.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @uses   get_user_meta()
	 *
	 * @return bool
	 */
	protected function is_dismissed() {
		// Get current user.
		$current_user = wp_get_current_user();

		// Check if current item is dismissed.
		return (bool) get_user_meta(
			$current_user->ID,
			$this->key( 'dismissed' ),
			true
		);
	}

	/**
	 * Check if current user has the capability.
	 *
	 * Before showing and processing the notice actions,
	 * current user should have the capability to do so.
	 *
	 * @since  1.0.0
	 * @uses   current_user_can()
	 * @access protected
	 *
	 * @return bool
	 */
	protected function is_capable() {
		return current_user_can( $this->cap );
	}

	/**
	 * Check if the current screen is allowed.
	 *
	 * Make sure the current page's screen ID is in
	 * allowed IDs list before showing a notice.
	 * If no screen ID is set, we will allow it in
	 * all pages (not recommended).
	 *
	 * @since  1.0.0
	 * @access protected
	 * @uses   get_current_screen()
	 *
	 * @return bool
	 */
	protected function in_screen() {
		// If not screen ID is set, show everywhere.
		if ( empty( $this->screens ) ) {
			return true;
		}

		// Get current screen.
		$screen = get_current_screen();

		// Check if current screen id is allowed.
		return ! empty( $screen->id ) && in_array( $screen->id, $this->screens, true );
	}

	/**
	 * Get the class names for notice div.
	 *
	 * Notice is using WordPress admin notices info notice styles.
	 * You can pass additional class names to customize it for your
	 * requirements in `classes` option when creating notice instance.
	 *
	 * @see    https://developer.wordpress.org/reference/hooks/admin_notices/
	 *
	 * @since  1.0.0
	 * @access protected
	 *
	 * @return string
	 */
	protected function get_classes() {
		// Required classes.
		$classes = array( 'notice', 'notice-info' );

		// Add extra classes.
		if ( ! empty( $this->classes ) && is_array( $this->classes ) ) {
			$classes = array_merge( $classes, $this->classes );
			$classes = array_unique( $classes );
		}

		return implode( ' ', $classes );
	}

	/**
	 * Get the default notice message for the review.
	 *
	 * This will be used only if the message is not passed through
	 * options array. You can also use `duckdev_reviews_notice_message` filter to modify
	 * the notice message.
	 * NOTE: We will not escape anything. You should do it yourself if you are adding a
	 * custom message.
	 *
	 * @since  1.0.2
	 * @access protected
	 *
	 * @return string
	 */
	protected function get_message() {
		// Get current user data.
		$current_user = wp_get_current_user();
		// Make sure we have name.
		$name = empty( $current_user->display_name ) ? __( 'friend', $this->domain ) : ucwords( $current_user->display_name );

		$message = sprintf(
		// translators: %1$s Current user's name, %2$s Plugin name, %3$d.
			esc_html__( 'Hey %1$s, I noticed you\'ve been using %2$s for more than %3$d days – that’s awesome! Could you please do me a BIG favor and give it a 5-star rating on WordPress? Just to help us spread the word and boost our motivation.', $this->domain ),
			esc_html( $name ),
			'<strong>' . esc_html( $this->name ) . '</strong>',
			(int) $this->days
		);

		/**
		 * Filter to modify review notice message.
		 *
		 * @param string $message Notice message.
		 * @param int    $days    Days to show review.
		 *
		 * @since 1.0.2
		 */
		return apply_filters( 'duckdev_reviews_notice_message', $message, $this->days );
	}

	/**
	 * Check if we can show the notice.
	 *
	 * > Current screen is an allowed screen (@since  1.0.0
	 *
	 * @access protected
	 *
	 * @see    Noticee::is_capable())
	 * > It's time to show the notice (@see Noticee::is_time())
	 * > User has not dismissed the notice (@see Noticee::is_dismissed())
	 *
	 * @see    Noticee::in_screen())
	 * > Current user has the required capability (@return bool
	 */
	protected function can_show() {
		return (
			$this->in_screen() &&
			$this->is_capable() &&
			$this->is_time() &&
			! $this->is_dismissed()
		);
	}

	/**
	 * Process the notice actions.
	 *
	 * If current user is capable process actions.
	 * > Later: Extend the time to show the notice.
	 * > Dismiss: Hide the notice to current user.
	 *
	 * @since  1.0.0
	 * @access protected
	 *
	 * @return void
	 */
	protected function actions() {
		// Only if required.
		if ( ! $this->in_screen() || ! $this->is_capable() ) {
			return;
		}

		// Get the current review action.
		$action = filter_input( INPUT_GET, $this->key( 'action' ), FILTER_SANITIZE_STRING );

		switch ( $action ) {
			case 'later':
				// Let's show after 2 times of days.
				$time = time() + ( $this->days * DAY_IN_SECONDS * 2 );
				update_site_option( $this->key( 'time' ), $time );
				break;
			case 'dismiss':
				// Do not show again to this user.
				update_user_meta(
					get_current_user_id(),
					$this->key( 'dismissed' ),
					true
				);
				break;
		}
	}

	/**
	 * Setup notice options to initialize class.
	 *
	 * Make sure the required options are set before
	 * initializing the class.
	 *
	 * @param string $slug    WP.org slug for plugin.
	 * @param string $name    Name of plugin.
	 * @param array  $options Array of options (@see Notice::get()).
	 *
	 * @since  1.0.0
	 * @access private
	 *
	 * @return void
	 */
	private function setup( $slug, $name, array $options ) {
		// Plugin name is required.
		if ( empty( $name ) || empty( $slug ) ) {
			return;
		}

		// Merge options.
		$options = wp_parse_args(
			$options,
			array(
				'days'          => 7,
				'screens'       => array(),
				'cap'           => 'manage_options',
				'classes'       => array(),
				'domain'        => 'duckdev',
				'action_labels' => array(),
			)
		);

		// Action button/link labels.
		$this->action_labels = wp_parse_args(
			(array) $options['action_labels'],
			array(
				'review'  => esc_html__( 'Ok, you deserve it', $this->domain ),
				'later'   => esc_html__( 'Nope, maybe later', $this->domain ),
				'dismiss' => esc_html__( 'I already did', $this->domain ),
			)
		);

		// Set options.
		$this->slug    = (string) $slug;
		$this->name    = (string) $name;
		$this->cap     = (string) $options['cap'];
		$this->days    = (int) $options['days'];
		$this->screens = (array) $options['screens'];
		$this->classes = (array) $options['classes'];
		$this->domain  = (string) $options['domain'];
		$this->prefix  = isset( $options['prefix'] ) ? (string) $options['prefix'] : str_replace( '-', '_', $this->slug );
		$this->message = empty( $options['message'] ) ? $this->get_message() : (string) $options['message'];
	}

	/**
	 * Create key by prefixing option name.
	 *
	 * Use this to create proper key for options.
	 *
	 * @param string $key Key.
	 *
	 * @since  1.0.0
	 * @access protected
	 *
	 * @return string
	 */
	private function key( $key ) {
		return $this->prefix . '_reviews_' . $key;
	}
}
