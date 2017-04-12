<?php
/**
 * BP Block Users Template Stack.
 *
 * @package BP_Block_Users
 * @subpackage Template_Stack
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The BP Block Users Template Stack.
 *
 * @since 1.0.0
 */
class BPBU_Template_Stack {

	/**
	 * Adds BP Block Users template files to the BP template stack.
	 *
	 * @since 1.0.0
	 *
	 * @param string $template  Located template path.
	 * @param array  $templates Array of templates to attempt to load.
	 *
	 * @return string The BP Block Users template path.
	 */
	public static function settings_load_template_filter( $template, $templates ) {

		// Only filter the template location when we're on the follow component pages.
		if ( ! bp_is_settings_component() || ! bp_current_user_can( 'bp_moderate' ) ) {
			return $template;
		}

		// No template has been found, so add the plugin's template file to the stack.
		// https://codex.buddypress.org/plugindev/upgrading-older-plugins-that-bundle-custom-templates-for-bp-1-7/.
		if ( empty( $template ) ) {

			// Register our template stack.
			bp_register_template_stack( 'BPBU_Template_Stack::get_template_directory', 14 );

			// Add the plugins.php file to give us something to inject into.
			$template = locate_template( 'members/single/plugins.php', false, false );

			// Add a hook so our content will be injected.
			add_action( 'bp_template_content', create_function( '', "
			   bp_get_template_part( 'members/single/settings/block-user' );
			" ) );
		}

		// Fire the deprecated filter.
		$template = bpbu_apply_filters_deprecated(
			'tba_bp_block_user_settings_load_template_filter',
			array( $template ),
			'1.0.0',
			'bpbu_settings_load_template_filter'
		);

		/**
		 * Filters the return of the BP Block Users found template.
		 *
		 * @since 1.0.0
		 *
		 * @param string $template The BP Block User template.
		 */
		return apply_filters( 'bpbu_settings_load_template_filter', $template );
	}

	/**
	 * Return the BP Block Users template directory.
	 *
	 * @since 1.0.0
	 *
	 * @return string The BP Block Users template directory.
	 */
	public static function get_template_directory() {
		return dirname( dirname( __FILE__ ) ) . '/templates';
	}

	/**
	 * Loads the block user settings screen.
	 *
	 * @since 1.0.0
	 */
	public static function settings_screen_block_user() {

		// Fire the deprecated action.
		bpbu_do_action_deprecated(
			'tba_bp_settings_screen_block_user',
			array(),
			'1.0.0',
			'bpbu_prevent_blocked_user_login'
		);

		/**
		 * Fires before BP Block User settings template is loaded.
		 *
		 * @since 1.0.0
		 */
		do_action( 'bpbu_settings_screen_block_user' );

		// Load the block user settings template.
		bp_core_load_template( 'members/single/settings/block-user' );
	}
}
