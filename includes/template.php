<?php
/**
 * BP Block Users Functions.
 *
 * @package BP_Block_Users
 * @subpackage Template
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Output the escaped block user settings message.
 *
 * @since 1.0.0
 *
 * @param int $user_id The user id.
 *
 * @return void
 */
function bpbu_block_user_settings_message( $user_id = 0 ) {
	echo esc_html( bpbu_get_block_user_settings_message( $user_id ) );
}

/**
 * Return the block user settings message.
 *
 * @since 1.0.0
 *
 * @param int $user_id The user id.
 *
 * @return string The `block-user` settings page message.
 */
function bpbu_get_block_user_settings_message( $user_id = 0 ) {

	// Set `user_id` to displayed user id, if no id is passed.
	if ( empty( $user_id ) ) {
		$user_id = bp_displayed_user_id();
	}

	// Set up our messages array, separated by location.
	$messages = array(
		'not-blocked' => __( 'This member is not currently blocked.', 'bp-block-users' ),
		'indefinite'  => __( 'This member is blocked indefinitely.', 'bp-block-users' ),
		/* translators: 1: formatted expiration date, 2: formatted expiration time */
		'timed'       => __( 'This member is blocked until %1$s at %2$s.', 'bp-block-users' ),
	);
	if ( is_admin() ) {
		$messages = array(
			'not-blocked' => __( 'This member is not currently blocked.', 'bp-block-users' ),
			'indefinite'  => __( 'This member is blocked indefinitely.', 'bp-block-users' ),
			/* translators: 1: formatted expiration date, 2: formatted expiration time */
			'timed'       => __( 'This member is blocked until %1$s at %2$s.', 'bp-block-users' ),
		);
	}

	// Set the default message.
	$message = $messages['not-blocked'];

	// If the user is not blocked, bail.
	if ( ! BPBU_User::is_blocked( $user_id ) ) {
		return $message;
	}

	// Get the user block expiration time.
	$expiration = BPBU_User::get_expiration( $user_id );

	// If the year 3000, the user is blocked indefinitely.
	if ( '3000-01-01 00:00:00' === $expiration ) {
		$message = $messages['indefinite'];

	// Display when the user's block will expire.
	} elseif ( strtotime( $expiration ) > time() ) {

		// Set the date and time of the block expiration.
		$date = mysql2date( bp_get_option( 'date_format' ), $expiration );
		$time = mysql2date( bp_get_option( 'time_format' ), $expiration );

		// Set the message with expiration time.
		$message = sprintf( $messages['timed'], $date, $time );
	}

	// Fire the deprecated filter.
	$message = bpbu_apply_filters_deprecated(
		'tba_bp_get_block_user_settings_message',
		array( $message, $user_id ),
		'1.0.0',
		'bpbu_get_block_user_settings_message'
	);

	/**
	 * Filters the return of `bpbu_get_block_user_settings_message()`.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message The BP Block User settings message.
	 * @param int    $user_id The user being checked.
	 */
	return apply_filters( 'bpbu_get_block_user_settings_message', $message, $user_id );
}

/**
 * Display the block user settings message on the `block-user` settings page.
 *
 * @since 1.0.0
 *
 * @return void
 */
function bpbu_block_users_show_settings_message() {

	// Bail if we're not on the `block-user` settings page.
	if ( ! bp_is_current_action( 'block-user' ) ) {
		return;
	}

	?>

		<div id="message" class="info">
			<p><?php bpbu_block_user_settings_message(); ?></p>
		</div>

	<?php
}
add_action( 'bp_before_member_settings_template', 'bpbu_block_users_show_settings_message' );
