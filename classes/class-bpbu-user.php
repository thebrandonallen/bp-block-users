<?php
/**
 * BP Block Users User class.
 *
 * @package BP_Block_Users
 * @subpackage User
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BP Block Users User class.
 *
 * @since 1.0.0
 */
class BPBU_User {

	/**
	 * Block the specified user and log them out of all current sessions.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $user_id User to block.
	 * @param int    $length  Numeric length of time to block user.
	 * @param string $unit    Unit of time to block user.
	 *
	 * @return int|bool True or meta id on success, false on failure.
	 */
	public static function block( $user_id = 0, $length = 0, $unit = 'indefintely' ) {

		// Bail if no user id.
		if ( empty( $user_id ) ) {
			return false;
		}

		// Only update the user meta if the user isn't blocked.
		bp_update_user_meta( $user_id, 'bpbu_user_blocked', 1 );

		// Update the expiration time and clear user sessions.
		if ( BPBU_User::is_blocked( $user_id ) ) {

			// Set the user block expiration date.
			BPBU_User::update_expiration( $user_id, $length, $unit );

			// Log the user out of all sessions.
			BPBU_User::destroy_sessions( $user_id );
		}

		// Fire the deprecated action.
		bpbu_do_action_deprecated(
			'tba_bp_blocked_user',
			array( $user_id ),
			'1.0.0',
			'bpbu_user_blocked'
		);

		/**
		 * Fires after a user is blocked.
		 *
		 * @since 1.0.0
		 *
		 * @param int  $user_id The blocked user id.
		 */
		do_action( 'bpbu_user_blocked', $user_id );

		return true;
	}

	/**
	 * Unblock the specified user.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User to block.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function unblock( $user_id = 0 ) {

		// Bail if no user id.
		if ( empty( $user_id ) ) {
			return false;
		}

		// Unblock the user.
		bp_delete_user_meta( $user_id, 'bpbu_user_blocked' );
		bp_delete_user_meta( $user_id, 'bpbu_user_blocked_expiration' );

		// Fire the deprecated action.
		bpbu_do_action_deprecated(
			'tba_bp_unblocked_user',
			array( $user_id ),
			'1.0.0',
			'bpbu_user_unblocked'
		);

		/**
		 * Fires after a user is unblocked.
		 *
		 * @since 1.0.0
		 *
		 * @param int  $user_id   The unblocked user id.
		 */
		do_action( 'bpbu_user_unblocked', $user_id );

		return true;
	}

	/**
	 * Update the expiration time of the blocked user.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $user_id User to block.
	 * @param int    $length  Numeric length of time to block user.
	 * @param string $unit    Unit of time to block user.
	 *
	 * @return int|bool True or meta id on success, false on failure.
	 */
	public static function update_expiration( $user_id = 0, $length = 0, $unit = 'indefinitely' ) {

		// Bail if no user id.
		if ( empty( $user_id ) ) {
			return false;
		}

		// Validate the length.
		$length = is_numeric( $length ) ? (int) $length : 0;

		// If no length, set unit to `indefinitely` to prevent immediate expiration.
		if ( empty( $length ) ) {
			$unit = 'indefinitely';
		}

		// Set up the units array.
		$units = array(
			'minutes' => MINUTE_IN_SECONDS,
			'hours'   => HOUR_IN_SECONDS,
			'days'    => DAY_IN_SECONDS,
			'weeks'   => WEEK_IN_SECONDS,
			'months'  => MONTH_IN_SECONDS,
		);

		// Fire the deprecated filter.
		$units = (array) bpbu_apply_filters_deprecated(
			'tba_bp_block_users_expiration_units',
			array( $units ),
			'1.0.0',
			'bpbu_expiration_units'
		);

		/**
		 * Filters the array of time units and their values.
		 *
		 * @since 1.0.0
		 *
		 * @param array $units The array of time units and their values.
		 */
		$units = (array) apply_filters( 'bpbu_expiration_units', $units );

		// In the year 3000...
		$expiration = '3000-01-01 00:00:00';

		// Set the expiration time.
		if ( array_key_exists( $unit, $units ) ) {
			$expiration = gmdate( 'Y-m-d H:i:s', ( time() + ( $length * $units[ $unit ] ) ) );
		}

		// Fire the deprecated filter.
		$expiration = bpbu_apply_filters_deprecated(
			'tba_bp_block_user_expiration_time',
			array( $expiration, $user_id, $length, $unit ),
			'1.0.0',
			'bpbu_update_user_blocked_expiration'
		);

		/**
		 * Filters the expiration time of a blocked user.
		 *
		 * @since 1.0.0
		 *
		 * @param string $expiration The expiration MySQL timestamp in GMT.
		 * @param int    $user_id    The blocked user id.
		 * @param int    $length     The numeric length of time user should be blocked.
		 * @param string $unit       The unit of time user should be blocked.
		 */
		$expiration = apply_filters( 'bpbu_update_user_blocked_expiration', $expiration, $user_id, $length, $unit );

		// Update the user blocked expiration meta.
		return bp_update_user_meta( $user_id, 'bpbu_user_blocked_expiration', $expiration );
	}

	/**
	 * Return the user's block expiration time.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id The blocked user.
	 *
	 * @return bool|string MySQL timestamp. `3000-01-01 00:00:00` if blocked
	 *                     indefinitely. False on failure.
	 */
	public static function get_expiration( $user_id = 0 ) {

		// Bail if no user id.
		if ( empty( $user_id ) ) {
			return false;
		}

		// Get the user block expiration MySQL timestamp.
		$expiration = bp_get_user_meta( $user_id, 'bpbu_user_blocked_expiration', true );

		// If the expiration time is empty, assume an indefinite block.
		if ( empty( $expiration ) ) {
			$expiration = '3000-01-01 00:00:00';
		}

		// Fire the deprecated filter.
		$expiration = bpbu_apply_filters_deprecated(
			'tba_bp_get_blocked_user_expiration',
			array( $expiration, $user_id ),
			'1.0.0',
			'bpbu_get_user_blocked_expiration'
		);

		/**
		 * Filters the return of `bpbu_get_user_blocked_expiration()`.
		 *
		 * @since 1.0.0
		 *
		 * @param bool|string $expiration MySQL timestamp. `3000-01-01 00:00:00` if
		 *                                blocked indefinitely. False on failure.
		 * @param int         $user_id    The blocked user id.
		 */
		return apply_filters( 'bpbu_get_user_blocked_expiration', $expiration, $user_id );
	}

	/**
	 * Check if the specified user is blocked.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User to check for a block.
	 *
	 * @return bool True if user is blocked.
	 */
	public static function is_blocked( $user_id = 0 ) {

		// Bail if no user id.
		if ( empty( $user_id ) ) {
			return false;
		}

		// Grab the boolean version of the `bp_user_blocked` meta value.
		$blocked = '1' === bp_get_user_meta( $user_id, 'bpbu_user_blocked', true );

		// If user is blocked, check the expiration.
		if ( $blocked ) {

			// If the user's block has expired, unblock them.
			$expiration = (int) strtotime( BPBU_User::get_expiration( $user_id ) );
			if ( $expiration < time() ) {

				if ( BPBU_User::unblock( $user_id ) ) {
					$blocked = false;
				}
			}
		}

		// Fire the deprecated filter.
		$blocked = (bool) bpbu_apply_filters_deprecated(
			'tba_bp_is_user_blocked',
			array( $blocked, $user_id ),
			'1.0.0',
			'bpbu_is_user_blocked'
		);

		/**
		 * Filters the return of `bpbu_is_user_blocked()`.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $blocked True if user is blocked.
		 * @param int  $user_id The blocked user id.
		 */
		return (bool) apply_filters( 'bpbu_is_user_blocked', $blocked, $user_id );
	}

	/**
	 * Return an array of blocked user ids.
	 *
	 * @since 1.0.0
	 *
	 * @return array An array of blocked user ids.
	 */
	public static function get_blocked_user_ids() {

		// Get the filtered meta keys.
		$blocked_key    = bp_get_user_meta_key( 'bpbu_user_blocked' );
		$expiration_key = bp_get_user_meta_key( 'bpbu_user_blocked_expiration' );

		// Get the current time with a 10 second buffer.
		$expiration_time = gmdate( 'Y-m-d H:i:s', ( time() + 10 ) );

		// Get the ids of all blocked users.
		$query = new WP_User_Query( array(
			'fields'      => 'ID',
			'count_total' => false,
			'orderby'     => 'ID',
			'meta_query'  => array(
				'relation' => 'AND',
				array(
					'key'   => $blocked_key,
					'value' => 1,
				),
				array(
					'key'     => $expiration_key,
					'value'   => $expiration_time,
					'type'    => 'DATETIME',
					'compare' => '>',
				),
			),
		) );

		// Cast as integers.
		$user_ids = array_map( 'intval', $query->results );

		// Fire the deprecated filter.
		$user_ids = (array) bpbu_apply_filters_deprecated(
			'tba_bp_get_blocked_user_ids',
			array( $user_ids ),
			'1.0.0',
			'bpbu_get_blocked_user_ids'
		);

		/**
		 * Filters the return of the blocked user ids array.
		 *
		 * @since 1.0.0
		 *
		 * @param array $user_ids The array of blocked user ids.
		 */
		return (array) apply_filters( 'bpbu_get_blocked_user_ids', $user_ids );
	}

	/**
	 * Destroys all the user sessions for the specified user.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id The blocked user.
	 *
	 * @return void
	 */
	public static function destroy_sessions( $user_id = 0 ) {

		// Bail if no user id.
		if ( empty( $user_id ) ) {
			return;
		}

		// Get the user's sessions object and destroy all sessions.
		WP_Session_Tokens::get_instance( $user_id )->destroy_all();
	}
}
