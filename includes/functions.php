<?php
/**
 * BP Block Users Functions.
 *
 * @package BP_Block_Users
 * @subpackage Functions
 */

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
 * @return int|bool True or meta id on success, false on failure.
 */
function tba_bp_block_user( $user_id = 0, $length = 0, $unit = 'indefintely' ) {

	// Bail if no user id.
	if ( empty( $user_id ) ) {
		return false;
	}

	// Set the user as blocked.
	$blocked = bp_update_user_meta( $user_id, 'tba_bp_user_blocked', 1 );
	if ( $blocked ) {

		// Set the user block expiration date.
		tba_bp_update_blocked_user_expiration( $user_id, $length, $unit );

		// Log the user out of all sessions.
		tba_bp_destroy_blocked_user_sessions( $user_id );
	}

	/**
	 * Fires after a user is blocked.
	 *
	 * @since 0.1.0
	 *
	 * @param int  $user_id The blocked user id.
	 * @param bool $blocked True on success, false on failure.
	 */
	do_action( 'tba_bp_blocked_user', $user_id, $blocked );

	return $blocked;
}

/**
 * Unblock the specified user.
 *
 * @since 0.1.0
 *
 * @param int $user_id User to block.
 *
 * @return bool True on success, false on failure.
 */
function tba_bp_unblock_user( $user_id = 0 ) {

	// Bail if no user id.
	if ( empty( $user_id ) ) {
		return false;
	}

	// Unblock the user.
	$unblocked = bp_delete_user_meta( $user_id, 'tba_bp_user_blocked' );
	if ( $unblocked ) {
		bp_delete_user_meta( $user_id, 'tba_bp_user_blocked_expiration' );
	}

	/**
	 * Fires after a user is unblocked.
	 *
	 * @since 0.1.0
	 *
	 * @param int  $user_id   The unblocked user id.
	 * @param bool $unblocked True on success, false on failure.
	 */
	do_action( 'tba_bp_unblocked_user', $user_id, $unblocked );

	return $unblocked;
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
	if ( in_array( $unit, array_keys( $units ), true ) ) {
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
 * @todo This should use WP_User_Query with a multi-relational meta query
 *       when WP 4.1 is the minimum.
 *
 * @global wpdb The WP database object.
 *
 * @return array An array of blocked user ids.
 */
function tba_bp_get_blocked_user_ids() {
	global $wpdb;

	// Get the filtered meta keys.
	$blocked_key    = bp_get_user_meta_key( 'tba_bp_user_blocked' );
	$expiration_key = bp_get_user_meta_key( 'tba_bp_user_blocked_expiration' );

	// Check the cache first.
	$user_ids = wp_cache_get( 'user_ids', 'bp_block_users' );

	// If the cache is empty, pull from the database.
	if ( false === $user_ids ) {
		$sql = $wpdb->prepare(
			"SELECT DISTINCT `m1`.`user_id`
				FROM {$wpdb->usermeta} AS `m1`
				INNER JOIN {$wpdb->usermeta} AS `m2` ON `m1`.`user_id` = `m2`.`user_id`
				WHERE `m1`.`meta_key` = %s
					AND `m1`.`meta_value` = '1'
					AND `m2`.`meta_key` = %s
					AND ( CAST(`m2`.`meta_value` AS DATETIME) > UTC_TIMESTAMP() OR `m2`.`meta_value` = '0' );",
			$blocked_key,
			$expiration_key
		);

		// Get the ids of all blocked users.
		$user_ids = array_map( 'absint', $wpdb->get_col( $sql ) );

		// Add the user ids to the cache.
		wp_cache_set( 'user_ids', $user_ids, 'bp_block_users' );
	}

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
 * Returns an array of blocked user `WP_User` objects.
 *
 * This function is a wrapper for `WP_User_Query` that only returns results for
 * users that are blocked. Any ids passed in the `includes` parameter that don't
 * belong to a blocked user will be filtered out.
 *
 * @since 0.2.0
 *
 * @param array $args Arguments to pass to `WP_User_Query`.
 *
 * @return WP_User_Query
 */
function tba_bp_get_blocked_users( $args = array() ) {

	// Get the blocked user ids.
	$user_ids = tba_bp_get_blocked_user_ids();

	// Set a default user query args.
	$r = array();

	// Set query vars if we have blocked users.
	if ( ! empty( $user_ids ) ) {

		// Make sure we have an array.
		$r = wp_parse_args( $args, array( 'count_total' => true ) );

		// Set the `includes` parameter to get our blocked user objects.
		if ( isset( $r['include'] ) ) {
			$r['include'] = array_intersect( (array) $r['include'], $user_ids );
		} else {
			$r['include'] = $user_ids;
		}
	}

	// Run the user query.
	$users = new WP_User_Query( $r );

	/**
	 * Filters the return of the blocked user objects array.
	 *
	 * @since 0.2.0
	 *
	 * @param array $users The array of blocked user objects.
	 */
	return apply_filters( 'tba_bp_get_blocked_users', $users );
}

/**
 * Destroys all the user sessions for the specified user.
 *
 * @since 0.2.0
 *
 * @param int $user_id The blocked user.
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

/* Cache **********************************************************************/

/**
 * Clean the BP Block Users cache.
 *
 * @since 0.2.0
 */
function bp_block_users_clean_cache() {
	wp_cache_delete( 'user_ids', 'bp_block_users' );
}
add_action( 'tba_bp_blocked_user', 'bp_block_users_clean_cache' );
add_action( 'tba_bp_unblocked_user', 'bp_block_users_clean_cache' );
