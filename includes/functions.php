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
	$sql = "SELECT DISTINCT `m1`.`user_id`
			FROM {$wpdb->usermeta} AS `m1`
			INNER JOIN {$wpdb->usermeta} AS `m2` ON `m1`.`user_id` = `m2`.`user_id`
			WHERE `m1`.`meta_key` = '{$blocked_key}'
				AND `m1`.`meta_value` = '1'
				AND `m2`.`meta_key` = '{$expiration_key}'
				AND ( CAST(`m2`.`meta_value` AS DATETIME) > UTC_TIMESTAMP() OR `m2`.`meta_value` = '0' );";

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
 * Destroys all the user sessions for the specified user.
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
function tba_bp_destroy_blocked_user_sessions( $user_id = 0 ) {

	// Bail if no user id.
	if ( empty( $user_id ) ) {
		return;
	}

	// Get the user's sessions object and destroy all sessions.
	$manager = WP_Session_Tokens::get_instance( $user_id );
	$manager->destroy_all();
}

/** Notification Emails *******************************************************/

/**
 * Prevent email notifications for blocked users.
 *
 * @since 0.1.0
 *
 * @deprecated 0.2.0
 *
 * @param mixed  $retval   Null or new short-circuited meta value.
 * @param int    $user_id  The user id.
 * @param string $meta_key The meta key.
 * @param bool   $single   Whether to return an array, or the the meta value.
 *
 * @uses BP_Block_Users_Component::block_notifications()
 *
 * @return mixed `no` if blocking a user email notification.
 */
function tba_bp_block_users_block_notifications( $retval, $user_id, $meta_key, $single ) {
	_deprecated_function(
		'tba_bp_block_users_block_notifications',
		'0.2.0',
		'BP_Block_Users_Component::block_notifications'
	);
	return buddypress()->block_users->block_notifications( $retval, $user_id, $meta_key, $single );
}

/** Authentication ************************************************************/

/**
 * Prevents the login of a blocked user.
 *
 * @since 0.1.0
 *
 * @deprecated 0.2.0
 *
 * @param null|WP_User $user The WP_User object being authenticated.
 *
 * @uses do_action_ref_array() To call the `tba_bp_authentication_blocked` hook.
 *
 * @return WP_User|WP_Error WP_User object if not blocked. WP_Error object,
 *                          otherwise. Passed by reference.
 */
function tba_bp_prevent_blocked_user_login( $user = null ) {
	_deprecated_function(
		'tba_bp_prevent_blocked_user_login',
		'0.2.0',
		'BP_Block_Users_Component::prevent_blocked_user_login'
	);
	return buddypress()->block_users->prevent_blocked_user_login( $user );
}

/** Sub-nav/Admin Bar Menus ***************************************************/

/**
 * Add the BP Block Users settings sub nav.
 *
 * @since 0.1.0
 *
 * @deprecated 0.2.0
 *
 * @uses BP_Block_Users_Component::setup_settings_sub_nav()
 *
 * @return void
 */
function tba_bp_block_user_settings_sub_nav() {
	_deprecated_function(
		'tba_bp_block_user_settings_sub_nav',
		'0.2.0',
		'BP_Block_Users_Component::setup_settings_sub_nav'
	);
	buddypress()->block_users->setup_settings_sub_nav();
}

/**
 * Add the `Block User` link to the WP Admin Bar.
 *
 * @since 0.1.0
 *
 * @deprecated 0.2.0
 *
 * @uses BP_Block_Users_Component::setup_settings_admin_bar()
 *
 * @return void
 */
function tba_bp_block_users_admin_bar_admin_menu() {
	_deprecated_function(
		'tba_bp_block_users_admin_bar_admin_menu',
		'0.2.0',
		'BP_Block_Users_Component::setup_settings_admin_bar'
	);
	buddypress()->block_users->setup_settings_admin_bar();
}

/** Settings Actions **********************************************************/

/**
 * Block/unblock a user when editing from a BP profile page.
 *
 * @since 0.1.0
 *
 * @deprecated 0.2.0
 *
 * @uses BP_Block_Users_Component::block_user_settings_action()
 *
 * @return void
 */
function tba_bp_settings_action_block_user() {
	_deprecated_function(
		'tba_bp_settings_action_block_user',
		'0.2.0',
		'BP_Block_Users_Component::block_user_settings_action'
	);
	buddypress()->block_users->block_user_settings_action();
}
