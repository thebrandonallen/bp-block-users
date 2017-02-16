<?php
/**
 * BP Block Users Functions.
 *
 * @package BP_Block_Users
 * @subpackage Deprecated
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/** Notification Emails *******************************************************/

/**
 * Prevent email notifications for blocked users.
 *
 * @since 0.1.0
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
		'BPBU_Component::block_notifications'
	);
	return buddypress()->block_users->block_notifications( $retval, $user_id, $meta_key, $single );
}

/** Authentication ************************************************************/

/**
 * Prevents the login of a blocked user.
 *
 * @since 0.1.0
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
		'BPBU_Component::prevent_blocked_user_login'
	);
	return buddypress()->block_users->prevent_blocked_user_login( $user );
}

/** Sub-nav/Admin Bar Menus ***************************************************/

/**
 * Add the BP Block Users settings sub nav.
 *
 * @since 0.1.0
 * @deprecated 0.2.0
 *
 * @return void
 */
function tba_bp_block_user_settings_sub_nav() {
	_deprecated_function(
		'tba_bp_block_user_settings_sub_nav',
		'0.2.0',
		'BPBU_Component::setup_settings_sub_nav'
	);
	buddypress()->block_users->setup_settings_sub_nav();
}

/**
 * Add the `Block User` link to the WP Admin Bar.
 *
 * @since 0.1.0
 * @deprecated 0.2.0
 *
 * @return void
 */
function tba_bp_block_users_admin_bar_admin_menu() {
	_deprecated_function(
		'tba_bp_block_users_admin_bar_admin_menu',
		'0.2.0',
		'BPBU_Component::setup_settings_admin_bar'
	);
	buddypress()->block_users->setup_settings_admin_bar();
}

/** Settings Actions **********************************************************/

/**
 * Block/unblock a user when editing from a BP profile page.
 *
 * @since 0.1.0
 * @deprecated 0.2.0
 *
 * @return void
 */
function tba_bp_settings_action_block_user() {
	_deprecated_function(
		'tba_bp_settings_action_block_user',
		'0.2.0',
		'BPBU_Component::settings_action'
	);
	buddypress()->block_users->settings_action();
}
