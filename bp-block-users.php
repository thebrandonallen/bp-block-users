<?php
/**
 * Plugin Name:     BP Block Users
 * Plugin URI:      https://github.com/thebrandonallen/bp-block-users
 * Description:     Allows BuddyPress administrators to block users indefinitely, or for a specified period of time.
 * Author:          Brandon Allen
 * Author URI:      https://github.com/thebrandonallen
 * Text Domain:     bp-block-users
 * Domain Path:     /languages
 * Version:         1.0.1
 *
 * Copyright (C) 2015-2017  Brandon Allen (https://github.com/thebrandonallen)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 * @package BP_Block_Users
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Only load the plugin code if BuddyPress is activated.
 *
 * @since 1.0.0
 */
function bpbu_init() {

	// Only supported in BP 2.4.0+.
	if ( version_compare( bp_get_version(), '2.4.0', '>=' ) ) {

		require plugin_dir_path( __FILE__ ) . 'classes/class-bpbu-component.php';

		add_action( 'bp_loaded', 'bpbu_setup_component' );

	} else {

		$older_version_notice = sprintf( __( 'Hey! BP Block Users requires BuddyPress 2.4.0 or higher.', 'bp-block-users' ) );
		add_action( 'admin_notices', create_function( '', "
			echo '<div class=\"error\"><p>" . $older_version_notice . "</p></div>';
		" ) );
		return;
	}
}
add_action( 'bp_include', 'bpbu_init' );

/**
 * Load the translation file for current language. Checks the BP Block Users
 * languages folder first, then inside the default WP language plugins folder.
 *
 * Note that custom translation files inside the BP Block Users plugin folder
 * will be removed on BP Block Users updates. If you're creating custom
 * translation files, please use the global language folder, located at
 * wp-content/languages/plugins.
 *
 * @since 1.0.0
 */
function bpbu_load_textdomain() {
	load_plugin_textdomain( 'bp-block-users', false, dirname( __FILE__ ) . '/languages/' );
}
add_action( 'plugins_loaded', 'bpbu_load_textdomain' );

/**
 * Loads the Block Users component into the $bp global.
 *
 * @since 1.0.0
 */
function bpbu_setup_component() {

	buddypress()->block_users = new BPBU_Component( __FILE__ );

	/**
	 * Fires after the BP Block Users component is loaded.
	 *
	 * @since 1.0.0
	 */
	do_action( 'bpbu_loaded' );
}

/**
 * The BP Block Users activation hook.
 *
 * @since 1.0.0
 */
function bpbu_activation() {

	/**
	 * Fires on plugin activation.
	 *
	 * @since 1.0.0
	 */
	do_action( 'bpbu_activation' );
}
register_activation_hook( __FILE__, 'bpbu_activation' );

/**
 * The BP Block Users deactivation hook.
 *
 * @since 1.0.0
 */
function bpbu_deactivation() {

	/**
	 * Fires on plugin deactivation.
	 *
	 * @since 1.0.0
	 */
	do_action( 'bpbu_deactivation' );
}
register_deactivation_hook( __FILE__, 'bpbu_deactivation' );

/* Constants ******************************************************************/

// `MONTH_IN_SECONDS` wasn't introduced until WP 4.4, so we add it here.
if ( ! defined( 'MONTH_IN_SECONDS' ) ) {
	define( 'MONTH_IN_SECONDS', ( 30 * DAY_IN_SECONDS ) );
}
