<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Output the escaped block user settings message.
 *
 * @since 0.1.0
 *
 * @param int $user_id
 *
 * @uses tba_bp_get_block_user_settings_message()
 * @uses esc_html() To escape input for HTML.
 *
 * @return void
 */
function tba_bp_block_user_settings_message( $user_id = 0 ) {
	echo esc_html( tba_bp_get_block_user_settings_message( $user_id ) );
}

/**
 * Return the block user settings message.
 *
 * @since 0.1.0
 *
 * @param int $user_id
 *
 * @uses bp_displayed_user_id() To get the displayed user id.
 * @uses tba_bp_is_user_blocked() To check if specified user is blocked.
 * @uses tba_bp_get_blocked_user_expiration() To get the blocked user expiration time.
 * @uses bp_get_option() To get the `date_format` and `time_format` options.
 * @uses date_i18n() To get the localized date from GMT date.
 * @uses get_date_from_gmt() To get the localized time from GMT date.
 *
 * @return string The `block-user` settings page message.
 */
function tba_bp_get_block_user_settings_message( $user_id = 0 ) {

	// Set `user_id` to displayed user id, if no id is passed.
	if ( empty( $user_id ) ) {
		$user_id = bp_displayed_user_id();
	}

	// Set the default message.
	$message = __( 'This member is not currently blocked.', 'bp-block-users' );

	// If the user is not blocked, bail.
	if ( ! tba_bp_is_user_blocked( $user_id ) ) {
		return $message;
	}

	// Get the user block expiration time.
	$expiration = tba_bp_get_blocked_user_expiration( $user_id );
	$expiration_int = strtotime( $expiration );

	// If the expiration is not a timestamp, the user is blocked indefinitely.
	if ( empty( $expiration ) ) {
		$message = __( 'This member is blocked indefinitely.', 'bp-block-users' );

	// Display when the user's block will expire.
	} elseif ( $expiration_int > time() ) {

		// Set the date and time of the block expiration.
		$date = date_i18n( bp_get_option( 'date_format' ), $expiration_int );
		$time = get_date_from_gmt( $expiration, bp_get_option( 'time_format' ) );

		// Set the message with expiration time.
		$message = sprintf( __( 'This member is blocked until %1$s at %2$s.', 'bp-block-users' ), $date, $time );
	}

	/**
	 * Filters the return of the BP Block Users found template.
	 *
	 * @since 0.1.0
	 *
	 * @param string $message The BP Block User settings message.
	 * @param int    $user_id The user being checked.
	 */
	return apply_filters( 'tba_bp_get_block_user_settings_message', $message, $user_id );
}

/**
 * Display the block user settings message on the `block-user` settings page.
 *
 * @since 0.1.0
 *
 * @uses bp_is_current_action() To check if we're on the `block-user` action page.
 * @uses tba_bp_block_user_settings_message() To get the blocked user settings message.
 *
 * @return void
 */
function tba_bp_block_users_show_settings_message() {

	// Bail if we're not on the `block-user` settings page.
	if ( ! bp_is_current_action( 'block-user' ) ) {
		return;
	}

	?>

		<div id="message" class="info">
			<p><?php tba_bp_block_user_settings_message(); ?></p>
		</div>

	<?php
}
add_action( 'bp_before_member_settings_template', 'tba_bp_block_users_show_settings_message' );
