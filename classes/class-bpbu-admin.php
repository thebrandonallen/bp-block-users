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
 * @since 1.0.0
 */
class BPBU_Admin {

	/**
	 * The BP Block Users Admin instance.
	 *
	 * @since 1.0.0
	 *
	 * @var BPBU_Admin
	 */
	private static $instance;

	/**
	 * The capability needed to edit users.
	 *
	 * Determined by `bp_core_do_network_admin()`.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $capability;

	/**
	 * Provides access to a single instance of `BPBU_Admin` using the singleton
	 * pattern.
	 *
	 * @since 1.0.0
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
	 * @since 1.0.0
	 */
	protected function __construct() {
		$this->setup_globals();
		$this->setup_actions();
		$this->setup_updater();

		BPBU_Admin_Profile::get_instance();
		BPBU_Admin_List_Tables::get_instance();
	}

	/**
	 * Set admin-related globals.
	 *
	 * @since 1.0.0
	 */
	private function setup_globals() {

		$this->capability = bp_core_do_network_admin() ? 'manage_network_users' : 'edit_users';
	}

	/**
	 * Set admin-related actions and filters.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function setup_actions() {

		// Display the admin notices.
		add_action( 'admin_notices', array( $this, 'display_notices' ) );
	}

	/**
	 * Setup the update routine.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function setup_updater() {

		// Bail if we're not updating.
		if ( ! $this->is_update() ) {
			return;
		}

		$this->version_updater();
	}

	/**
	 * Checks if the install needs updating.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	private function is_update() {
		$db_version_raw = (int) get_option( '_bpbu_db_version', 0 );
		$retval         = ( $db_version_raw < BPBU_Component::DB_VERSION );
		return (bool) $retval;
	}

	/**
	 * Runs the version updater.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function version_updater() {
		global $wpdb;

		// Get the current database version.
		$db_version_raw = (int) get_option( '_bpbu_db_version', 0 );

		// 1.0.0.
		if ( $db_version_raw < 20 ) {

			// Rename the `tba_bp_user_blocked` meta key.
			$wpdb->update(
				$wpdb->usermeta,
				array(
					'meta_key' => 'bpbu_user_blocked',
				),
				array(
					'meta_key' => 'tba_bp_user_blocked',
				),
				array( '%s' ),
				array( '%s' )
			);

			// Rename the `tba_bp_user_blocked_expiration` meta key.
			$wpdb->update(
				$wpdb->usermeta,
				array(
					'meta_key' => 'bpbu_user_blocked_expiration',
				),
				array(
					'meta_key' => 'tba_bp_user_blocked_expiration',
				),
				array( '%s' ),
				array( '%s' )
			);

			// Set indefinite expirations to the year 3000.
			$wpdb->update(
				$wpdb->usermeta,
				array(
					'meta_value' => '3000-01-01 00:00:00',
				),
				array(
					'meta_key'   => 'bpbu_user_blocked_expiration',
					'meta_value' => '0',
				),
				array( '%s' ),
				array( '%s', '%d' )
			);
		} // End if().

		// Bump the database version.
		update_option( '_bpbu_db_version', BPBU_Component::DB_VERSION );
	}

	/**
	 * Get admin notices when viewing the blocked users page.
	 *
	 * @since 1.0.0
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
	 * @since 1.0.0
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
	 * @since 1.0.0
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
		 * @since 1.0.0
		 *
		 * @param bool $retval  True if the user can (un)block.
		 * @param int  $user_id The user id being (un)blocked.
		 */
		return apply_filters( 'bpbu_admin_current_user_can_block', $retval, $user_id );
	}

	/**
	 * Remove any query args we don't need.
	 *
	 * @since 1.0.0
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
