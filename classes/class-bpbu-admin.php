<?php
/**
 * BP Block Users Admin.
 *
 * @package BP_Block_Users
 * @subpackage Administration
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Load BP Block Users admin area.
 *
 * @since 0.2.0
 */
class BPBU_Admin {

	/**
	 * The BP Block Users Admin instance.
	 *
	 * @since 0.2.0
	 *
	 * @var BPBU_Admin
	 */
	private static $instance;

	/**
	 * The capability needed to edit users.
	 *
	 * Determined by `bp_core_do_network_admin()`.
	 *
	 * @since 0.2.0
	 *
	 * @var string
	 */
	protected $capability;

	/**
	 * Provides access to a single instance of `BPBU_Admin` using the singleton
	 * pattern.
	 *
	 * @since 0.2.0
	 *
	 * @return BPBU_Admin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Constructor method.
	 *
	 * @since 0.2.0
	 */
	protected function __construct() {
		$this->setup_globals();
		$this->setup_actions();

		BPBU_Admin_Profile::get_instance();
		BPBU_Admin_List_Tables::get_instance();
	}

	/**
	 * Set admin-related globals.
	 *
	 * @since 0.2.0
	 */
	private function setup_globals() {

		$this->capability = bp_core_do_network_admin() ? 'manage_network_users' : 'edit_users';
	}

	/**
	 * Set admin-related actions and filters.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	private function setup_actions() {

		// Display the admin notices.
		add_action( 'admin_notices', array( $this, 'display_notices' ) );
	}

	/**
	 * Get admin notices when viewing the blocked users page.
	 *
	 * @since 0.2.0
	 *
	 * @return array An array of notices. Defaults to an empty array.
	 */
	private function get_notices() {

		// Setup empty notice for return value.
		$notices = array();

		// Updates.
		if ( ! empty( $_REQUEST['updated'] ) && 'unblocked' === $_REQUEST['updated'] ) {

			if ( ! empty( $_REQUEST['unblocked'] ) ) {
				$unblocked = count( explode( ',', $_REQUEST['unblocked'] ) );
				$notices['updated'] = sprintf(
					/* translators: Unblocked users count */
					_nx( '%s user unblocked.', '%s users unblocked.',
						$unblocked,
						'user unblocked',
						'bp-block-users'
					),
					number_format_i18n( $unblocked )
				);
			}

			if ( ! empty( $_REQUEST['notunblocked'] ) ) {
				$notunblocked = count( explode( ',', $_REQUEST['notunblocked'] ) );
				$notices['error'] = sprintf(
					/* translators: Failed unblocked users count */
					_nx( '%s user not unblocked.', '%s users not unblocked.',
						$notunblocked,
						'user not unblocked',
						'bp-block-users'
					),
					number_format_i18n( $notunblocked )
				);
			}
		}

		return $notices;
	}

	/**
	 * Output our admin notices.
	 *
	 * @since 0.2.0
	 */
	public function display_notices() {

		// Get the notices.
		$notices = $this->get_notices();

		// Display notices.
		foreach ( $notices as $class => $message ) :
			if ( empty( $message ) ) {
				continue;
			}

			if ( 'updated' === $class ) : ?>

				<div id="message" class="<?php echo esc_attr( $class ); ?>">

			<?php else : ?>

				<div class="<?php echo esc_attr( $class ); ?>">

			<?php endif; ?>

				<p><?php echo esc_html( $message ); ?></p>

			</div>

		<?php endforeach;
	}

	/**
	 * Can the current user (un)block a specified user?
	 *
	 * A user can't block themselves, and they must have the `bp_moderate` cap.
	 *
	 * @since 0.2.0
	 *
	 * @param int $user_id ID of the user being (un)blocked.
	 *
	 * @return bool
	 */
	protected function current_user_can_block( $user_id = 0 ) {

		// Default to can't (un)block.
		$retval = false;

		// User can't edit their own profile.
		if ( get_current_user_id() !== (int) $user_id ) {

			// Trust the 'bp_moderate' capability.
			$retval = bp_current_user_can( 'bp_moderate' );
		}

		/**
		 * Filters the return of the admin current user can block function.
		 *
		 * @since 0.2.0
		 *
		 * @param bool $retval  True if the user can (un)block.
		 * @param int  $user_id The user id being (un)blocked.
		 */
		return apply_filters( 'bpbu_admin_current_user_can_block', $retval, $user_id );
	}

	/**
	 * Remove any query args we don't need.
	 *
	 * @since 0.2.0
	 *
	 * @param string $url The url to be acted upon.
	 *
	 * @return string
	 */
	protected function remove_query_args( $url ) {
		return remove_query_arg(
			array(
				'action',
				'action2',
				'error',
				'notunblocked',
				'unblock',
				'unblocked',
				'updated',
				'user_id',
				'_wpnonce',
			),
			$url
		);
	}
}
