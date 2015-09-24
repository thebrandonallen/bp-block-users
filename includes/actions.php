<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Admin
if ( is_admin() ) {

	// Add block user options to profile pages.
	add_action( 'edit_user_profile', 'tba_bp_block_users_settings_fields' );

	// Update.
	add_action( 'user_profile_update_errors', 'tba_bp_block_users_update_user_settings', 10, 3 );

	// Add a row action to users listing.
	if ( bp_core_do_network_admin() ) {
		add_filter( 'ms_user_row_actions', 'tba_bp_block_users_row_actions', 10, 2 );
	}

	// Add user row actions for single site.
	add_filter( 'user_row_actions', 'tba_bp_block_users_row_actions', 10, 2 );
}

// Set all notification emails to "no".
add_filter( 'get_user_metadata', 'tba_bp_block_users_block_notifications', 10, 4 );

// Add the BP Block Users template to template stack.
add_filter( 'bp_located_template', 'tba_bp_block_user_settings_load_template_filter', 10, 2 );

// Add block user settings sub nav.
add_action( 'bp_settings_setup_nav', 'tba_bp_block_user_settings_sub_nav' );

// Prevent the login of a blocked user.
add_action( 'authenticate', 'tba_bp_prevent_blocked_user_login', 40 );

// Add the our admin bar link.
add_action( 'admin_bar_menu', 'tba_bp_block_users_admin_bar_admin_menu', 100 );

// Block/unblock user when editing from profile.
add_action( 'bp_actions', 'tba_bp_settings_action_block_user' );

// Logout a currently logged-in user that was just blocked.
add_action( 'bp_init', 'tba_bp_stop_live_blocked_user', 5 );

// Set a custom error message when a logged-in user is blocked and redirected.
add_action( 'login_form_tba-bp-blocked-user',      'tba_bp_live_blocked_user_login_error' );
add_action( 'login_form_tba-bp-blocked-user-temp', 'tba_bp_live_blocked_user_login_error' );
