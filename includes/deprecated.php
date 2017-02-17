<?php
/**
 * BP Block Users Functions.
 *
 * @package BP_Block_Users
 * @subpackage Deprecated
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/* Admin **********************************************************************/

/**
 * Output the block user settings field on admin user edit page.
 *
 * @since 0.1.0
 * @deprecated 0.2.0
 *
 * @param WP_User $user The WP_User object.
 *
 * @return void
 */
function tba_bp_block_users_settings_fields( $user ) {
	_deprecated_function(
		'tba_bp_block_users_settings_fields',
		'0.2.0'
	);
}

/**
 * Update the block user settings.
 *
 * @since 0.1.0
 * @deprecated 0.2.0
 *
 * @param WP_Error $errors
 * @param bool     $update
 * @param WP_User  $user
 *
 * @return void
 */
function tba_bp_block_users_update_user_settings( $errors, $update, $user ) {
	_deprecated_function(
		'tba_bp_block_users_update_user_settings',
		'0.2.0'
	);
}

/**
 * Add a `Block/Unblock` link to the user row action links.
 *
 * @since 0.1.0
 * @deprecated 0.2.0
 *
 * @param array        $actions An array of row actions.
 * @param null|WP_User $user    The WP_User object.
 *
 * @return array An array of row actions.
 */
function tba_bp_block_users_row_actions( $actions = array(), $user = null ) {
	_deprecated_function(
		'tba_bp_block_users_row_actions',
		'0.2.0'
	);
	return $actions;
}

/* User Functions *************************************************************/

/**
 * Block the specified user and log them out of all current sessions.
 *
 * @since 0.1.0
 * @deprecated 0.2.0
 *
 * @param int    $user_id User to block.
 * @param int    $length  Numeric length of time to block user.
 * @param string $unit    Unit of time to block user.
 *
 * @return int|bool True or meta id on success, false on failure.
 */
function tba_bp_block_user( $user_id = 0, $length = 0, $unit = 'indefintely' ) {
	_deprecated_function(
		'tba_bp_block_user',
		'0.2.0',
		'BPBU_User::block'
	);
	return BPBU_User::block( $user_id, $length, $unit );
}

/**
 * Unblock the specified user.
 *
 * @since 0.1.0
 * @deprecated 0.2.0
 *
 * @param int $user_id User to block.
 *
 * @return bool True on success, false on failure.
 */
function tba_bp_unblock_user( $user_id = 0 ) {
	_deprecated_function(
		'tba_bp_unblock_user',
		'0.2.0',
		'BPBU_User::unblock'
	);
	return BPBU_User::unblock( $user_id );
}

/**
 * Update the expiration time of the blocked user.
 *
 * @since 0.1.0
 * @deprecated 0.2.0
 *
 * @param int    $user_id User to block.
 * @param int    $length  Numeric length of time to block user.
 * @param string $unit    Unit of time to block user.
 *
 * @return int|bool True or meta id on success, false on failure.
 */
function tba_bp_update_blocked_user_expiration( $user_id = 0, $length = 0, $unit = 'indefinitely' ) {
	_deprecated_function(
		'tba_bp_update_blocked_user_expiration',
		'0.2.0',
		'BPBU_User::update_expiration'
	);
	return BPBU_User::update_expiration( $user_id, $length, $unit );
}

/**
 * Return the user's block expiration time.
 *
 * @since 0.1.0
 * @deprecated 0.2.0
 *
 * @param int  $user_id The blocked user.
 * @param bool $int     Whether to return a Unix timestamp.
 *
 * @return mixed MySQL expiration timestamp. Unix if `$int` is true. Zero if
 *               blocked indefinitely. False on failure.
 */
function tba_bp_get_blocked_user_expiration( $user_id = 0, $int = false ) {
	_deprecated_function(
		'tba_bp_get_blocked_user_expiration',
		'0.2.0',
		'BPBU_User::get_expiration'
	);
	return BPBU_User::get_expiration( $user_id, $int );
}

/**
 * Check if the specified user is blocked.
 *
 * @since 0.1.0
 * @deprecated 0.2.0
 *
 * @param int $user_id User to check for a block.
 *
 * @return bool True if user is blocked.
 */
function tba_bp_is_user_blocked( $user_id = 0 ) {
	_deprecated_function(
		'tba_bp_is_user_blocked',
		'0.2.0',
		'BPBU_User::is_blocked'
	);
	return BPBU_User::is_blocked( $user_id );
}

/**
 * Return an array of blocked user ids.
 *
 * @since 0.1.0
 * @deprecated 0.2.0
 *
 * @return array An array of blocked user ids.
 */
function tba_bp_get_blocked_user_ids() {
	_deprecated_function(
		'tba_bp_get_blocked_user_ids',
		'0.2.0',
		'BPBU_User::get_blocked_user_ids'
	);
	return BPBU_User::get_blocked_user_ids( $user_id );
}

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

/* Theme Compat ***************************************************************/

/**
 * Adds BP Block Users template files to the BP template stack.
 *
 * @since 0.1.0
 * @deprecated 0.2.0
 *
 * @param string $template  Located template path.
 * @param array  $templates Array of templates to attempt to load.
 *
 * @return string The BP Block Users template path.
 */
function tba_bp_block_user_settings_load_template_filter( $template, $templates ) {
	_deprecated_function(
		'tba_bp_block_user_settings_load_template_filter',
		'0.2.0',
		'BPBU_Template_Stack::settings_load_template_filter'
	);
	return BPBU_Template_Stack::settings_load_template_filter( $template, $templates );
}

/**
 * Return the BP Block Users template directory.
 *
 * @since 0.1.0
 * @deprecated 0.2.0
 *
 * @return string The BP Block Users template directory.
 */
function tba_bp_block_user_get_template_directory() {
	_deprecated_function(
		'tba_bp_block_user_get_template_directory',
		'0.2.0',
		'BPBU_Template_Stack::get_template_directory'
	);
	return BPBU_Template_Stack::get_template_directory();
}

/**
 * Loads the block user settings screen.
 *
 * @since 0.1.0
 * @deprecated 0.2.0
 */
function tba_bp_settings_screen_block_user() {
	_deprecated_function(
		'tba_bp_settings_screen_block_user',
		'0.2.0',
		'BPBU_Template_Stack::settings_screen_block_user'
	);
	BPBU_Template_Stack::settings_screen_block_user();
}
