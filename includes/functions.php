<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/** User Functions ************************************************************/

/**
 * Block the specified user and log them out of all current sessions.
 *
 * @since 0.1.0
 *
 * @param int    $user_id User to block.
 * @param int    $length  Numeric length of time to block user.
 * @param string $unit    Unit of time to block user.
 *
 * @uses bp_update_user_meta() To update the user meta.
 * @uses tba_bp_update_blocked_user_expiration() To update the blocked user expiration time.
 * @uses bp_delete_user_meta() To delete the user meta.
 * @uses do_action() To call the `tba_bp_blocked_user` hook.
 *
 * @return int|bool True or meta id on success, false on failure.
 */
function tba_bp_block_user( $user_id = 0, $length = 0, $unit = 'indefintely' ) {

	// Bail if no user id.
	if ( empty( $user_id ) ) {
		return false;
	}

	// Set the user as blocked.
	bp_update_user_meta( $user_id, 'tba_bp_user_blocked', 1 );

	// Set the user block expiration date.
	tba_bp_update_blocked_user_expiration( $user_id, $length, $unit );

	// Log the user out of all sessions.
	tba_bp_destroy_blocked_user_sessions( $user_id );

	/**
	 * Fires after a user is blocked.
	 *
	 * @since 0.1.0
	 *
	 * @param int $user_id The blocked user id.
	 */
	do_action( 'tba_bp_blocked_user', $user_id );

	return true;
}

/**
 * Unblock the specified user.
 *
 * @since 0.1.0
 *
 * @param int $user_id User to block.
 *
 * @uses bp_delete_user_meta() To delete the user meta.
 * @uses do_action() To call the `tba_bp_unblocked_user` hook.
 *
 * @return bool True on success, false on failure.
 */
function tba_bp_unblock_user( $user_id = 0 ) {

	// Bail if no user id.
	if ( empty( $user_id ) ) {
		return false;
	}

	// Unblock the user.
	$deleted = bp_delete_user_meta( $user_id, 'tba_bp_user_blocked' );
	if ( $deleted ) {
		bp_delete_user_meta( $user_id, 'tba_bp_user_blocked_expiration' );
	}

	/**
	 * Fires after a user is unblocked.
	 *
	 * @since 0.1.0
	 *
	 * @param int  $user_id The unblocked user id.
	 * @param bool $deleted True on success, false on failure.
	 */
	do_action( 'tba_bp_unblocked_user', $user_id, $deleted );

	return $deleted;
}

/**
 * Update the expiration time of the blocked user.
 *
 * @since 0.1.0
 *
 * @param int    $user_id User to block.
 * @param int    $length  Numeric length of time to block user.
 * @param string $unit    Unit of time to block user.
 *
 * @uses apply_filters() To call the `tba_bp_block_users_expiration_units` and
 *                       `tba_bp_block_user_expiration_time` filters.
 * @uses bp_update_user_meta() To update the user meta.
 *
 * @return int|bool True or meta id on success, false on failure.
 */
function tba_bp_update_blocked_user_expiration( $user_id = 0, $length = 0, $unit = 'indefinitely' ) {

	// Bail if no user id.
	if ( empty( $user_id ) ) {
		return false;
	}

	// Validate the length.
	$length = (int) $length;

	// If no length, set unit to `indefinitely` to prevent immediate expiration.
	if ( empty( $length ) ) {
		$unit = 'indefinitely';
	}

	/**
	 * Filters the array of time units and their values.
	 *
	 * @since 0.1.0
	 *
	 * @param array $units The array of time units and their values.
	 */
	$units = apply_filters( 'tba_bp_block_users_expiration_units', array(
		'minutes' => MINUTE_IN_SECONDS,
		'hours'   => HOUR_IN_SECONDS,
		'days'    => DAY_IN_SECONDS,
		'weeks'   => WEEK_IN_SECONDS,
		'months'  => MONTH_IN_SECONDS,
	) );

	// Set the default expiration.
	$expiration = 0;

	// Set the expiration time.
	if ( in_array( $unit, array_keys( $units ) ) ) {
		$expiration = gmdate( 'Y-m-d H:i:s', ( time() + ( $length * $units[ $unit ] ) ) );
	}

	/**
	 * Filters the expiration time of a blocked user.
	 *
	 * @since 0.1.0
	 *
	 * @param string $expiration The expiration MySQL timestamp in GMT.
	 * @param int    $user_id    The blocked user id.
	 * @param int    $length     The numeric length of time user should be blocked.
	 * @param string $unit       The unit of time user should be blocked.
	 */
	$expiration = apply_filters( 'tba_bp_block_user_expiration_time', $expiration, $user_id, $length, $unit );

	// Update the user blocked expiration meta.
	return bp_update_user_meta( $user_id, 'tba_bp_user_blocked_expiration', $expiration );
}

/**
 * Return the user's block expiration time.
 *
 * @since 0.1.0
 *
 * @param int  $user_id The blocked user.
 * @param bool $int     Whether to return a Unix timestamp.
 *
 * @uses bp_update_user_meta() To update the user meta.
 *
 * @return mixed MySQL expiration timestamp. Unix if `$int` is true. Zero if
 *               blocked indefinitely. False on failure.
 */
function tba_bp_get_blocked_user_expiration( $user_id = 0, $int = false ) {

	// Bail if no user id.
	if ( empty( $user_id ) ) {
		return false;
	}

	// Get the user block expiration MySQL timestamp.
	$expiration = bp_get_user_meta( $user_id, 'tba_bp_user_blocked_expiration', true );

	// If the expiration time is empty, assume an indefinite block.
	if ( empty( $expiration ) ) {
		$expiration = 0;
	}

	// If we want an integer, convert the MySQL timestamp to a Unix timestamp.
	if ( $int ) {
		$expiration = (int) strtotime( $expiration );
	}

	/**
	 * Filters the return of the BP Block Users found template.
	 *
	 * @since 0.1.0
	 *
	 * @param string|int $expiration MySQL expiration timestamp. Unix if `$int` is
	 *                               true. Zero if blocked indefinitely.
	 * @param int        $user_id    The blocked user id.
	 */
	return apply_filters( 'tba_bp_get_blocked_user_expiration', $expiration, $user_id );
}

/**
 * Check if the specified user is blocked.
 *
 * @since 0.1.0
 *
 * @param int $user_id User to check for a block.
 *
 * @uses bp_update_user_meta() To update the user meta.
 * @uses tba_bp_get_blocked_user_expiration() To get the blocked user expiration time.
 * @uses tba_bp_unblock_user() To unblock the specified user.
 * @uses apply_filters() To call the `tba_bp_is_user_blocked` filter.
 *
 * @return bool True if user is blocked.
 */
function tba_bp_is_user_blocked( $user_id = 0 ) {

	// Bail if no user id.
	if ( empty( $user_id ) ) {
		return false;
	}

	// Grab the boolean version of the `bp_user_blocked` meta value.
	$blocked = (bool) absint( bp_get_user_meta( $user_id, 'tba_bp_user_blocked', true ) );

	// If user is blocked, check the expiration.
	if ( $blocked ) {

		// If the user's block has expired, unblock them.
		$expiration = tba_bp_get_blocked_user_expiration( $user_id, true );
		if ( ! empty( $expiration ) && $expiration < time() ) {

			if ( tba_bp_unblock_user( $user_id ) ) {
				$blocked = false;
			}
		}
	}

	/**
	 * Filters the return of the BP Block Users found template.
	 *
	 * @since 0.1.0
	 *
	 * @param bool $blocked True if user is blocked.
	 * @param int  $user_id The blocked user id.
	 */
	return (bool) apply_filters( 'tba_bp_is_user_blocked', $blocked, $user_id );
}

/**
 * Return an array of blocked user ids.
 *
 * @since 0.1.0
 *
 * @global wpdb The WP database object.
 *
 * @uses bp_get_user_meta_key() To get a filtered version of the meta key.
 * @uses wpdb::get_col() To get an array of all blocked user ids.
 * @uses apply_filters() To call the `tba_bp_get_blocked_user_ids` filter.
 *
 * @return array An array of blocked user ids.
 */
function tba_bp_get_blocked_user_ids() {
	global $wpdb;

	// Get the filtered meta keys.
	$blocked_key    = bp_get_user_meta_key( 'tba_bp_user_blocked' );
	$expiration_key = bp_get_user_meta_key( 'tba_bp_user_blocked_expiration' );

	// Setup the query.
	$sql = "SELECT DISTINCT e.`user_id`
			FROM {$wpdb->usermeta} AS e
			WHERE `user_id` IN
					( SELECT `b`.`user_id` FROM {$wpdb->usermeta} AS b WHERE `b`.`meta_key` = '{$blocked_key}' AND `b`.`meta_value` = '1' )
				AND
					`e`.`meta_key` = '{$expiration_key}' AND ( CAST(`e`.`meta_value` AS DATETIME) > UTC_TIMESTAMP() OR `e`.`meta_value` = '0' );";

	// Get the ids of all blocked users.
	$user_ids = $wpdb->get_col( $sql );

	/**
	 * Filters the return of the blocked user ids array.
	 *
	 * @since 0.1.0
	 *
	 * @param array $user_ids The array of blocked user ids.
	 */
	return (array) apply_filters( 'tba_bp_get_blocked_user_ids', $user_ids );
}

/**
 * tba_bp_destroy_blocked_user_sessions function.
 *
 * @since 0.2.0
 *
 * @param int $user_id The blocked user.
 *
 * @uses WP_Session_Tokens::get_instance() To get the specified user's sessions object.
 * @uses WP_Session_Tokens::destroy_all() To destroy all the user's sessions.
 *
 * @return void
 */
function tba_bp_destroy_blocked_user_sessions( $user_id = 0) {

	// Bail if no user id.
	if ( empty( $user_id ) ) {
		return;
	}

	// Get the user's sessions object and destroy all session.
	$manager = WP_Session_Tokens::get_instance( $user_id );
	$manager->destroy_all();
}

/** Notification Emails *******************************************************/

/**
 * Prevent email notifications for blocked users.
 *
 * @since 0.1.0
 *
 * @param mixed  $retval   Null or new short-circuited meta value.
 * @param int    $user_id  The user id.
 * @param string $meta_key The meta key.
 * @param bool   $single   Whether to return an array, or the the meta value.
 *
 * @uses apply_filters() To call the `tba_bp_block_users_block_notifications_meta_keys`
 *                       and `tba_bp_block_users_block_notifications_value` filters.
 * @uses bp_get_user_meta_key() To get a filtered version of the meta key.
 * @uses tba_bp_is_user_blocked() To check if specified user is blocked.
 *
 * @return mixed `no` if blocking a user email notification.
 */
function tba_bp_block_users_block_notifications( $retval, $user_id, $meta_key, $single ) {

	// Bail early if we have no user id or meta key.
	if ( empty( $user_id ) || empty( $meta_key ) ) {
		return $retval;
	}

	/**
	 * Filters the array of notification meta keys to block.
	 *
	 * @since 0.1.0
	 *
	 * @param array $keys MySQL expiration timestamp. Unix if `$int` is
	 */
	$keys = apply_filters(
		'tba_bp_block_users_block_notifications_meta_keys',
		array_map( 'bp_get_user_meta_key', array(
			'notification_activity_new_mention',
			'notification_activity_new_reply',
			'notification_friends_friendship_request',
			'notification_friends_friendship_accepted',
			'notification_groups_invite',
			'notification_groups_group_updated',
			'notification_groups_admin_promotion',
			'notification_groups_membership_request',
			'notification_messages_new_message',
		) )
	);

	// Bail if we're not checking a notification key.
	if ( ! in_array( $meta_key, $keys ) ) {
		return $retval;
	}

	// If the value is not already `no` and the user is blocked, set to `no`.
	if ( 'no' !== $retval && tba_bp_is_user_blocked( $user_id ) ) {
		$retval = 'no';
	}

	/**
	 * Filters the return of the notification meta value.
	 *
	 * @since 0.1.0
	 *
	 * @param mixed  $retval   Null or new short-circuited meta value.
	 * @param int    $user_id  The user id.
	 * @param string $meta_key The meta key.
	 * @param bool   $single   Whether to return an array, or the the meta value.
	 */
	return apply_filters( 'tba_bp_block_users_block_notifications_value', $retval, $user_id, $meta_key, $single );
}

/** Authentication ************************************************************/

/**
 * bp_prevent_blocked_user_login function.
 *
 * @since 0.1.0
 *
 * @param null|WP_User $user The WP_User object being authenticated.
 *
 * @uses is_wp_error() To for a WP_Error object.
 * @uses tba_bp_is_user_blocked() To check if specified user is blocked.
 * @uses tba_bp_get_blocked_user_expiration() To get the blocked user expiration time.
 * @uses WP_Error() To add the `tba_bp_authentication_blocked` error message.
 * @uses do_action_ref_array() To call the `tba_bp_authentication_blocked` hook.
 *
 * @return WP_User|WP_Error WP_User object if not blocked. WP_Error object,
 *                          otherwise. Passed by reference.
 */
function tba_bp_prevent_blocked_user_login( $user ) {

	// Bail early if login has already failed.
	if ( is_wp_error( $user ) || empty( $user ) ) {
		return $user;
	}

	// Bail if no user id.
	if ( ! ( $user instanceof WP_User ) ) {
		return $user;
	}

	// Set the user id.
	$user_id = (int) $user->ID;

	// If the user is blocked, set the wp-login.php error message.
	if ( tba_bp_is_user_blocked( $user_id ) ) {

		// Set the default message.
		$message = __( '<strong>ERROR</strong>: This account has been blocked.', 'bp-block-users' );

		// Check to see if this is a temporary block.
		$expiration = tba_bp_get_blocked_user_expiration( $user_id, true );
		if ( ! empty( $expiration ) ) {
			$message = __( '<strong>ERROR</strong>: This account has been temporarily blocked.', 'bp-block-users' );
		}

		// Set an error object to short-circuit the authentication process.
		$user = new WP_Error( 'tba_bp_authentication_blocked', $message );
	}

	/**
	 * Filters the return of the authenticating user object.
	 *
	 * @since 0.2.0
	 *
	 * @param WP_User|WP_Error $user    WP_User object if not blocked. WP_Error
	 *                                  object, otherwise.
	 * @param int              $user_id Whether this is a user update.
	 */
	return apply_filters( 'tba_bp_prevent_blocked_user_login', $user, $user_id );
}

/** Sub-nav/Admin Bar Menus ***************************************************/

/**
 * Add the BP Block Users settings sub nav.
 *
 * @since 0.1.0
 *
 * @uses bp_current_user_can() To check the `bp_moderate` capability.
 * @uses bp_is_my_profile() To check if logged in user is viewing own profile.
 * @uses bp_displayed_user_domain() To get the displayed user domain.
 * @uses bp_get_settings_slug() To get the BP settings slug.
 * @uses trailingslashit() To add a trailingslash to the settings link.
 * @uses bp_displayed_user_id() To get the displayed user id.
 * @uses is_super_admin() To check if current user is super admin.
 * @uses bp_core_new_subnav_item() To add the `block-users` sub-nav.
 *
 * @return void
 */
function tba_bp_block_user_settings_sub_nav() {

	// Only show for those with `bp_moderate` or if you're not on your own profile.
	if ( ! bp_current_user_can( 'bp_moderate' ) || bp_is_my_profile() ) {
		return;
	}

	// Get the displayed user domain, or bail.
	if ( bp_displayed_user_domain() ) {
		$user_domain = bp_displayed_user_domain();
	} else {
		return;
	}

	// Set up the settings link.
	$slug          = bp_get_settings_slug();
	$settings_link = trailingslashit( $user_domain . $slug );

	// Set up the sub nav args array.
	$nav = array(
		'name'            => __( 'Block User', 'bp-block-users' ),
		'slug'            => 'block-user',
		'parent_url'      => $settings_link,
		'parent_slug'     => $slug,
		'screen_function' => 'tba_bp_settings_screen_block_user',
		'position'        => 85,
		'user_has_access' => ! is_super_admin( bp_displayed_user_id() ),
	);

	// Add the sub nav.
	bp_core_new_subnav_item( $nav );
}

/**
 * Add the `Block User` link to the WP Admin Bar.
 *
 * @since 0.1.0
 *
 * @uses bp_is_user() To check if we're viewing a user page.
 * @uses bp_current_user_can() To check the `bp_moderate` capability.
 * @uses bp_is_my_profile() To check if logged in user is viewing own profile.
 * @uses buddypress() To get the BP object.
 * @uses bp_is_active() To check if the `settings` component is active.
 * @uses WP_Admin_Bar::add_menu() To add the `Block User` link to the WP Admin Bar.
 * @uses bp_displayed_user_domain() To get the displayed user domain.
 *
 * @return void
 */
function tba_bp_block_users_admin_bar_admin_menu() {

	// Only show if viewing a user.
	if ( ! bp_is_user() ) {
		return;
	}

	// Don't show this menu to non site admins or if you're viewing your own profile.
	if ( ! bp_current_user_can( 'bp_moderate' ) || bp_is_my_profile() ) {
		return;
	}

	global $wp_admin_bar;

	// Set up the BP global.
	$bp = buddypress();

	// Add our `Block User` link to the WP admin bar.
	if ( bp_is_active( 'settings' ) ) {
		// User Admin > Block User.
		$wp_admin_bar->add_menu( array(
			'parent' => $bp->user_admin_menu_id,
			'id'     => $bp->user_admin_menu_id . '-block-user',
			'title'  => __( 'Block User', 'bp-block-users' ),
			'href'   => bp_displayed_user_domain() . 'settings/block-user/'
		) );
	}
}

/** Settings Actions **********************************************************/

/**
 * Block/unblock a user when editing from a BP profile page.
 *
 * @since 0.1.0
 *
 * @uses bp_is_settings_component() To check if we're on a settings component page.
 * @uses bp_is_current_action() To check if we're on the `block-user` action page.
 * @uses bp_action_variables() To check if there are extra action variables.
 * @uses bp_do_404() To trigger a 404.
 * @uses bp_current_user_can() To check the `bp_moderate` capability.
 * @uses bp_is_my_profile() To check if logged in user is viewing own profile.
 * @uses check_admin_referer() To check the `block-user` nonce.
 * @uses do_action() To call the `tba_bp_settings_block_user_before_save` and
 *                   `tba_bp_settings_block_user_after_save` hooks.
 * @uses sanitize_key() To sanitize the `block-user-unit` $_POST key.
 * @uses bp_displayed_user_id() To get the displayed user id.
 * @uses tba_bp_block_user() To block the specified user.
 * @uses tba_bp_unblock_user() To unblock the specified user.
 *
 * @return void
 */
function tba_bp_settings_action_block_user() {

	// Bail if not a POST action.
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
		return;
	}

	// Bail if no submit action.
	if ( ! isset( $_POST['block-user-submit'] ) ) {
		return;
	}

	// Bail if not in settings or the `block-user` action.
	if ( ! bp_is_settings_component() || ! bp_is_current_action( 'block-user' ) ) {
		return;
	}

	// 404 if there are any additional action variables attached.
	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	// If can't `bp_moderate` or on own profile, bail.
	if ( ! bp_current_user_can( 'bp_moderate' ) || bp_is_my_profile() ) {
		return;
	}

	// Nonce check.
	check_admin_referer( 'block-user' );

	/**
	 * Fires before the block user settings have been saved.
	 *
	 * @since 0.1.0
	 */
	do_action( 'tba_bp_settings_block_user_before_save' );

	// Sanitize our $_POST variables.
	$block  = isset( $_POST['block-user'] ) ? absint( $_POST['block-user'] ) : 0;
	$length = isset( $_POST['block-user-length'] ) ? absint( $_POST['block-user-length'] ) : 0;
	$unit   = isset( $_POST['block-user-unit'] ) ? sanitize_key( $_POST['block-user-unit'] ) : 'indefintely';

	// Block/unblock the user.
	if ( ! empty( $block ) ) {
		tba_bp_block_user( bp_displayed_user_id(), $length, $unit );
	} else {
		tba_bp_unblock_user( bp_displayed_user_id() );
	}

	/**
	 * Fires after the block user settings have been saved.
	 *
	 * @since 0.1.0
	 */
	do_action( 'tba_bp_settings_block_user_after_save' );
}
