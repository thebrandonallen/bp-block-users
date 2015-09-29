<?php

/*
Plugin Name: BP Block Users
Plugin URI:  http://github.com/thebrandonallen/bp-block-users/
Description: Allows BuddyPress administrators to block users indefinitely, or for a specified period of time.
Version:     0.1.0
Author:      Brandon Allen
Author URI:  http://github.com/thebrandonallen/
License:     GPLv2 or later (license.txt)
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages
Text Domain: bp-block-users
*/

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Only load the plugin code if BuddyPress is activated.
 */
function tba_bp_block_users_init() {

	// Only supported in BP 2.0.3+
	if ( version_compare( bp_get_version(), '2.0.3', '>=' ) ) {

		$includes = plugin_dir_path( __FILE__ ) . 'includes/';

		require $includes . 'actions.php';
		require $includes . 'functions.php';
		require $includes . 'template.php';
		require $includes . 'theme-compat.php';

		if ( is_admin() ) {
			require $includes . 'admin.php';
		}

	// Show admin notice for users on BP 1.9.x and below.
	} else {

		$older_version_notice = sprintf( __( 'Hey! BP Block Users requires BuddyPress 2.0.3 or higher.', 'bp-block-users' ) );
		add_action( 'admin_notices', create_function( '', "
			echo '<div class=\"error\"><p>" . $older_version_notice . "</p></div>';
		" ) );
		return;
	}
}
add_action( 'bp_include', 'tba_bp_block_users_init' );

/**
 * Load the translation file for current language. Checks the BP Block Users
 * languages folder first, then inside the default WP language plugins folder.
 *
 * Note that custom translation files inside the BP Block Users plugin folder
 * will be removed on BP Block Users updates. If you're creating custom
 * translation files, please use the global language folder (ie - wp-content/languages/plugins).
 *
 * @uses load_plugin_textdomain() To load the textdomain inside the 'plugin/languages' folder.
 *
 * @return void
 */
function tba_bp_block_users_load_textdomain() {

	// Look in wp-content/plugins/bp-block-users/languages first
	// fallback to wp-content/languages/plugins
	load_plugin_textdomain( 'bp-block-users', false, dirname( __FILE__ ) . '/languages/' );
}
add_action( 'plugins_loaded', 'tba_bp_block_users_load_textdomain' );
