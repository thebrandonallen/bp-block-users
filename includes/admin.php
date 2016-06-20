<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Output the block user settings field on admin user edit page.
 *
 * @since 0.1.0
 *
 * @param WP_User $user The WP_User object.
 *
 * @uses get_current_user_id() To get the current logged in user id.
 * @uses current_user_can() To check for the `edit_user` capability.
 * @uses bp_current_user_can() To check the `bp_moderate` capability.
 * @uses esc_html_e() To escape, echo, and translate passed string.
 * @uses tba_bp_is_user_blocked() To check if specified user is blocked.
 * @uses checked() To output the `checked="checked"` HTML.
 * @uses tba_bp_block_user_settings_message() To get the blocked user settings message.
 *
 * @return void
 */
function tba_bp_block_users_settings_fields( $user ) {

	// Bail if no user id.
	if ( empty( $user->ID ) ) {
		return;
	}

	// Bail if editing own profile.
	if ( (int) $user->ID === get_current_user_id() ) {
		return;
	}

	// Bail if not allowed to edit this user.
	if ( ! current_user_can( 'edit_user', $user->ID ) || ! bp_current_user_can( 'bp_moderate' ) ) {
		return;
	}

	?>

	<h2><?php esc_html_e( 'Block member', 'bp-block-users' ); ?></h2>
	<p><?php esc_html_e( 'Block this member indefinitely or for a specified amount of time.', 'bp-block-users' ); ?> <br /><span class="description"><?php esc_html_e( 'If "Indefinitely" is chosen, the time-length field will be ignored.', 'bp-block-users' ); ?></span></p>
	<table class="form-table">
		<tbody><tr>
			<th scope="row"><?php esc_html_e( 'Block member', 'bp-block-users' ); ?></th>
			<td>
				<label for="block-user">
					<input type="checkbox" name="block-user" id="block-user" value="1" <?php checked( tba_bp_is_user_blocked( $user->ID ) ); ?> />
					<?php esc_html_e( 'Block this member?', 'bp-block-users' ); ?>
				</label>
				<p class="description"><?php tba_bp_block_user_settings_message( $user->ID ); ?></p>
			</td>
		</tr></tbody>
		<tbody><tr>
			<th scope="row"><?php esc_html_e( 'Expiration', 'bp-block-users' ); ?></th>
			<td>
				<label for="block-user-length" class="screen-reader-text"><?php esc_html_e( 'Numeric length of time member should be blocked.', 'bp-block-users' ); ?></label>
				<input type="text" name="block-user-length" id="block-user-length" value="0" size="3" />

				<label for="block-user-unit" class="screen-reader-text"><?php esc_html_e( 'Unit of time a member should be blocked.', 'bp-block-users' ); ?></label>
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
 * @since 0.1.0
 *
 * @param WP_Error $errors
 * @param bool     $update
 * @param WP_User  $user
 *
 * @uses get_current_user_id() To get the current logged in user id.
 * @uses current_user_can() To check for the `edit_user` capability.
 * @uses bp_current_user_can() To check the `bp_moderate` capability.
 * @uses check_admin_referer() To check the `'update-user_' . $user->ID` nonce.
 * @uses sanitize_key() To sanitize the `block-user-unit` $_POST key.
 * @uses tba_bp_block_user() To block the specified user.
 * @uses tba_bp_unblock_user() To unblock the specified user.
 *
 * @return void
 */
function tba_bp_block_users_update_user_settings( $errors, $update, $user ) {

	// We shouldn't be here if we're not updating
	if ( ! $update ) {
		return;
	}

	// Bail if no user id.
	if ( empty( $user->ID ) ) {
		return;
	}

	// Bail if not super admin, or editing own profile.
	if ( (int) $user->ID === get_current_user_id() ) {
		return;
	}

	// Bail if not allowed to edit this user.
	if ( ! current_user_can( 'edit_user', $user->ID ) || ! bp_current_user_can( 'bp_moderate' ) ) {
		return;
	}

	// Check the nonce.
	check_admin_referer( 'update-user_' . $user->ID );

	// Sanitize our $_POST variables.
	$block  = isset( $_POST['block-user'] ) ? absint( $_POST['block-user'] ) : 0;
	$length = isset( $_POST['block-user-length'] ) ? absint( $_POST['block-user-length'] ) : 0;
	$unit   = isset( $_POST['block-user-unit'] ) ? sanitize_key( $_POST['block-user-unit'] ) : 'indefintely';

	// Block/unblock the user.
	if ( ! empty( $block ) ) {
		tba_bp_block_user( $user->ID, $length, $unit );
	} else {
		tba_bp_unblock_user( $user->ID );
	}
}

/**
 * Add a `Block/Unblock` link to the user row action links.
 *
 * @since 0.1.0
 *
 * @param array        $actions An array of row actions.
 * @param null|WP_User $user    The WP_User object.
 *
 * @uses get_current_user_id() To get the current logged in user id.
 * @uses current_user_can() To check for the `edit_user` capability.
 * @uses bp_current_user_can() To check the `bp_moderate` capability.
 * @uses wp_unslash() To unslash the passed string.
 * @uses tba_bp_is_user_blocked() To check if specified user is blocked.
 * @uses buddypress() To get the BP object.
 * @uses add_query_arg() To add query args to the passed URL.
 * @uses esc_url() To escape the passed URL.
 * @uses esc_html() To escape input for HTML.
 *
 * @return array An array of row actions.
 */
function tba_bp_block_users_row_actions( $actions = array(), $user = null ) {

	// Validate the user_id.
	if ( empty( $user->ID ) ) {
		return $actions;
	}

	// Bail if own row.
	if ( (int) $user->ID === get_current_user_id() ) {
		return $actions;
	}

	// Bail if not allowed to edit this user.
	if ( ! current_user_can( 'edit_user', $user->ID ) || ! bp_current_user_can( 'bp_moderate' ) ) {
		return $actions;
	}

	// Setup args array.
	$args = array();

	// Add the user ID.
	$args['user_id'] = $user->ID;

	// Add the referer.
	$args['wp_http_referer'] = urlencode( wp_unslash( $_SERVER['REQUEST_URI'] ) );

	// Setup the Block/Unblock text.
	$block_user_text = tba_bp_is_user_blocked( $user->ID ) ? __( 'Unblock', 'bp-block-users' ) : __( 'Block', 'bp-block-users' );

	// Add query args and setup the Block/Unblock link.
	$block_user_url  = add_query_arg( $args, buddypress()->members->admin->edit_url . '#block-user' );
	$block_user_link = sprintf( '<a href="%1$s">%2$s</a>',  esc_url( $block_user_url ), esc_html( $block_user_text ) );

	// Add the block link to the actions array.
	$actions['block'] = $block_user_link;

	return $actions;
}
