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
 * BP Block Users Admin Settings API functions.
 *
 * @since 1.0.0
 */
class BPBU_Admin_Profile extends BPBU_Admin {

	/**
	 * The BP Block Users Admin Profile instance.
	 *
	 * @since 1.0.0
	 *
	 * @var BPBU_Admin_Profile
	 */
	private static $instance;

	/**
	 * Provides access to a single instance of `BPBU_Admin_Profile` using the
	 * singleton pattern.
	 *
	 * @since 1.0.0
	 *
	 * @return BPBU_Admin_Profile
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
		$this->setup_actions();
	}

	/**
	 * Set admin-related actions and filters.
	 *
	 * @since 1.0.0
	 */
	public function setup_actions() {

		// Add block user options to profile pages.
		add_action( 'edit_user_profile', array( $this, 'settings_fields' ) );

		// Update.
		add_action( 'user_profile_update_errors', array( $this, 'update_user_settings' ), 10, 3 );
	}

	/**
	 * Output the block user settings field on admin user edit page.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_User $user The WP_User object.
	 *
	 * @return void
	 */
	public function settings_fields( $user ) {

		// Bail if no user id.
		if ( empty( $user->ID ) ) {
			return;
		}

		// Bail if current user can't block.
		if ( ! $this->current_user_can_block( $user->ID ) ) {
			return;
		}

		?>

		<h2><?php esc_html_e( 'Block User', 'bp-block-users' ); ?></h2>
		<p><?php esc_html_e( 'Block this user indefinitely or for a specified amount of time.', 'bp-block-users' ); ?> <br /><span class="description"><?php esc_html_e( 'If "Indefinitely" is chosen, the time-length field will be ignored.', 'bp-block-users' ); ?></span></p>
		<table class="form-table">
			<tbody><tr>
				<th scope="row"><?php esc_html_e( 'Block User', 'bp-block-users' ); ?></th>
				<td>
					<label for="block-user">
						<input type="checkbox" name="block-user" id="block-user" value="1" <?php checked( BPBU_User::is_blocked( $user->ID ) ); ?> />
						<?php esc_html_e( 'Block this user?', 'bp-block-users' ); ?>
					</label>
					<p class="description"><?php bpbu_block_user_settings_message( $user->ID ); ?></p>
				</td>
			</tr></tbody>
			<tbody><tr>
				<th scope="row"><?php esc_html_e( 'Expiration', 'bp-block-users' ); ?></th>
				<td>
					<label for="block-user-length" class="screen-reader-text"><?php esc_html_e( 'Numeric length of time user should be blocked.', 'bp-block-users' ); ?></label>
					<input type="text" name="block-user-length" id="block-user-length" value="0" size="3" />

					<label for="block-user-unit" class="screen-reader-text"><?php esc_html_e( 'Unit of time a user should be blocked.', 'bp-block-users' ); ?></label>
					<select name="block-user-unit" id="block-user-unit">
						<option value="minutes"><?php esc_html_e( 'minute(s)', 'bp-block-users' ); ?></option>
						<option value="hours"><?php esc_html_e( 'hour(s)', 'bp-block-users' ); ?></option>
						<option value="days"><?php esc_html_e( 'day(s)', 'bp-block-users' ); ?></option>
						<option value="weeks"><?php esc_html_e( 'week(s)', 'bp-block-users' ); ?></option>
						<option value="months"><?php esc_html_e( 'month(s)', 'bp-block-users' ); ?></option>
						<option value="indefinitely" selected="selected"><?php esc_html_e( 'Indefinitely', 'bp-block-users' ); ?></option>
					</select>
				</td>
			</tr></tbody>
		</table>

		<?php
	}

	/**
	 * Update the block user settings.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Error $errors The errors object.
	 * @param bool     $update True if the user is being updated.
	 * @param WP_User  $user   The user object.
	 *
	 * @return void
	 */
	public function update_user_settings( $errors, $update, $user ) {

		// We shouldn't be here if we're not updating.
		if ( ! $update ) {
			return;
		}

		// Bail if no user id.
		if ( empty( $user->ID ) ) {
			return;
		}

		// Bail if current user can't block.
		if ( ! $this->current_user_can_block( $user->ID ) ) {
			return;
		}

		// Check the nonce.
		check_admin_referer( 'update-user_' . $user->ID );

		// Get the $_POST variables.
		$post = BPBU_Component::get_block_user_post_vars();

		// Block/unblock the user.
		$success = null;
		if ( $post['block'] ) {
			$success = BPBU_User::block( $user->ID, $post['length'], $post['unit'] );
		} elseif ( BPBU_User::is_blocked( $user->ID ) ) {
			$success = BPBU_User::unblock( $user->ID );
		}

		// Add an error message if there was a failure.
		if ( false === $success ) {

			$messages = array(
				'block'   => __( '<strong>ERROR</strong>: The user could not be blocked.', 'bp-block-users' ),
				'unblock' => __( '<strong>ERROR</strong>: The user could not be unblocked.', 'bp-block-users' ),
			);

			$message = $post['block'] ? $messages['block'] : $messages['unblock'];

			$errors->add( 'bpbu_user_block', $message );
		}
	}
}
