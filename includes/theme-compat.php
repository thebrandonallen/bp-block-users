<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Adds BP Block Users template files to the BP template stack.
 *
 * @since 0.1.0
 *
 * @param string $template
 * @param array  $templates
 *
 * @uses bp_is_settings_component() To check if we're on a settings component page.
 * @uses bp_current_user_can() To check the `bp_moderate` capability.
 * @uses bp_register_template_stack() To register the BP Block Users template directory.
 * @uses locate_template() To locate the `block-user` template.
 *
 * @return string The BP Block Users template path.
 */
function tba_bp_block_user_settings_load_template_filter( $template, $templates ) {

	// Only filter the template location when we're on the follow component pages.
	if ( ! bp_is_settings_component() || ! bp_current_user_can( 'bp_moderate' ) ) {
		return $template;
	}

	// No template has been found, so add the plugin's template file to the stack.
	// https://codex.buddypress.org/plugindev/upgrading-older-plugins-that-bundle-custom-templates-for-bp-1-7/
	if ( empty( $template ) ) {

		// Register our template stack.
		bp_register_template_stack( 'tba_bp_block_user_get_template_directory', 14 );

		// Add the plugins.php file to give us something to inject into.
		$template = locate_template( 'members/single/plugins.php', false, false );

		// Add a hook so our content will be injected.
		add_action( 'bp_template_content', create_function( '', "
		   bp_get_template_part( 'members/single/settings/block-user' );
		" ) );
	}

	/**
	 * Filters the return of the BP Block Users found template.
	 *
	 * @since 0.1.0
	 *
	 * @param string $template The BP Block User template.
	 */
	return apply_filters( 'tba_bp_block_user_settings_load_template_filter', $template );
}

/**
 * Return the BP Block Users template directory.
 *
 * @since 0.1.0
 *
 * @return string The BP Block Users template directory.
 */
function tba_bp_block_user_get_template_directory() {
	return dirname( dirname( __FILE__ ) ) . '/templates';
}

/**
 * bp_settings_screen_block_account function.
 *
 * @since 0.1.0
 *
 * @uses do_action() To call the `tba_bp_settings_screen_block_user` hook.
 * @uses bp_core_load_template()
 *
 * @return void
 */
function tba_bp_settings_screen_block_user() {

	/**
	 * Fires before BP Block User settings template is loaded.
	 *
	 * @since 0.1.0
	 */
	do_action( 'tba_bp_settings_screen_block_user' );

	// Load the block user settings template.
	bp_core_load_template( 'members/single/settings/block-user' );
}

