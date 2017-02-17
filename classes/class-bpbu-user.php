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
 * @since 0.2.0
 */
class BPBU_User {

	/**
	 * Block the specified user and log them out of all current sessions.
	 *
	 * @since 0.2.0
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
		if ( BPBU_User::is_blocked( $user_id ) ) {
			$blocked = true;
		} else {
			$blocked = bp_update_user_meta( $user_id, 'tba_bp_user_blocked', 1 );
		}

		// Update the expiration time and clear user sessions.
		if ( $blocked ) {

			// Set the user block expiration date.
			BPBU_User::update_expiration( $user_id, $length, $unit );

			// Log the user out of all sessions.
			BPBU_User::destroy_sessions( $user_id );
		}

		// Fire the deprecated action.
		bpbu_do_action_deprecated(
			'tba_bp_blocked_user',
			array( $user_id, $blocked ),
			'0.2.0',
			'bpbu_blocked_user'
		);

		/**
		 * Fires after a user is blocked.
		 *
		 * @since 0.1.0
		 *
		 * @param int  $user_id The blocked user id.
		 * @param bool $blocked True on success, false on failure.
		 */
		do_action( 'bpbu_blocked_user', $user_id, $blocked );

		return $blocked;
	}

	/**
	 * Unblock the specified user.
	 *
	 * @since 0.2.0
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
		$unblocked = bp_delete_user_meta( $user_id, 'tba_bp_user_blocked' );
		if ( $unblocked ) {
			bp_delete_user_meta( $user_id, 'tba_bp_user_blocked_expiration' );
		}

		// Fire the deprecated action.
		bpbu_do_action_deprecated(
			'tba_bp_unblocked_user',
			array( $user_id, $unblocked ),
			'0.2.0',
			'bpbu_unblocked_user'
		);

		/**
		 * Fires after a user is unblocked.
		 *
		 * @since 0.1.0
		 *
		 * @param int  $user_id   The unblocked user id.
		 * @param bool $unblocked True on success, false on failure.
		 */
		do_action( 'bpbu_unblocked_user', $user_id, $unblocked );

		return $unblocked;
	}

	/**
	 * Update the expiration time of the blocked user.
	 *
	 * @since 0.2.0
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
		$length = (int) $length;

		// If no length, set unit to `indefinitely` to prevent immediate expiration.
		if ( empty( $length ) ) {
			$unit = 'indefinitely';
		}

		/**
		 * Filters the array of time units and their values.
		 *
		 * @since 0.1.0
		 *
		 * @param array $units The array of time units and their values.
		 */
		$units = apply_filters( 'tba_bp_block_users_expiration_units', array(
			'minutes' => MINUTE_IN_SECONDS,
			'hours'   => HOUR_IN_SECONDS,
			'days'    => DAY_IN_SECONDS,
			'weeks'   => WEEK_IN_SECONDS,
			'months'  => MONTH_IN_SECONDS,
		) );

		// Set the default expiration.
		$expiration = 0;

		// Set the expiration time.
		if ( in_array( $unit, array_keys( $units ), true ) ) {
			$expiration = gmdate( 'Y-m-d H:i:s', ( time() + ( $length * $units[ $unit ] ) ) );
		}

		/**
		 * Filters the expiration time of a blocked user.
		 *
		 * @since 0.1.0
		 *
		 * @param string $expiration The expiration MySQL timestamp in GMT.
		 * @param int    $user_id    The blocked user id.
		 * @param int    $length     The numeric length of time user should be blocked.
		 * @param string $unit       The unit of time user should be blocked.
		 */
		$expiration = apply_filters( 'tba_bp_block_user_expiration_time', $expiration, $user_id, $length, $unit );

		// Update the user blocked expiration meta.
		return bp_update_user_meta( $user_id, 'tba_bp_user_blocked_expiration', $expiration );
	}

	/**
	 * Return the user's block expiration time.
	 *
	 * @since 0.1.0
	 *
	 * @param int  $user_id The blocked user.
	 * @param bool $int     Whether to return a Unix timestamp.
	 *
	 * @return mixed MySQL expiration timestamp. Unix if `$int` is true. Zero if
	 *               blocked indefinitely. False on failure.
	 */
	public static function get_expiration( $user_id = 0, $int = false ) {

		// Bail if no user id.
		if ( empty( $user_id ) ) {
			return false;
		}

		// Get the user block expiration MySQL timestamp.
		$expiration = bp_get_user_meta( $user_id, 'tba_bp_user_blocked_expiration', true );

		// If the expiration time is empty, assume an indefinite block.
		if ( empty( $expiration ) ) {
			$expiration = 0;
		}

		// If we want an integer, convert the MySQL timestamp to a Unix timestamp.
		if ( $int ) {
			$expiration = (int) strtotime( $expiration );
		}

		/**
		 * Filters the return of the BP Block Users found template.
		 *
		 * @since 0.1.0
		 *
		 * @param string|int $expiration MySQL expiration timestamp. Unix if `$int` is
		 *                               true. Zero if blocked indefinitely.
		 * @param int        $user_id    The blocked user id.
		 */
		return apply_filters( 'tba_bp_get_blocked_user_expiration', $expiration, $user_id );
	}

	/**
	 * Check if the specified user is blocked.
	 *
	 * @since 0.1.0
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
		$blocked = '1' === bp_get_user_meta( $user_id, 'tba_bp_user_blocked', true );

		// If user is blocked, check the expiration.
		if ( $blocked ) {

			// If the user's block has expired, unblock them.
			$expiration = BPBU_User::get_expiration( $user_id, true );
			if ( ! empty( $expiration ) && $expiration < time() ) {

				if ( BPBU_User::unblock( $user_id ) ) {
					$blocked = false;
				}
			}
		}

		/**
		 * Filters the return of the BP Block Users found template.
		 *
		 * @since 0.1.0
		 *
		 * @param bool $blocked True if user is blocked.
		 * @param int  $user_id The blocked user id.
		 */
		return (bool) apply_filters( 'bpbu_is_user_blocked', $blocked, $user_id );
	}

	/**
	 * Return an array of blocked user ids.
	 *
	 * @since 0.1.0
	 *
	 * @return array An array of blocked user ids.
	 */
	public static function get_blocked_user_ids() {

		// Check the cache first.
		$user_ids = wp_cache_get( 'user_ids', 'bp_block_users' );

		// If the cache is empty, pull from the database.
		if ( false === $user_ids ) {

			// Get the filtered meta keys.
			$blocked_key    = bp_get_user_meta_key( 'tba_bp_user_blocked' );
			$expiration_key = bp_get_user_meta_key( 'tba_bp_user_blocked_expiration' );

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
						'relation' => 'OR',
						array(
							'key'   => $expiration_key,
							'value' => 0,
						),
						array(
							'key'     => $expiration_key,
							'value'   => $expiration_time,
							'type'    => 'DATETIME',
							'compare' => '>',
						),
					),
				),
			) );

			// Cast as integers.
			$user_ids = array_map( 'intval', $query->results );

			// Add the user ids to the cache.
			wp_cache_set( 'user_ids', $user_ids, 'bp_block_users' );
		}

		/**
		 * Filters the return of the blocked user ids array.
		 *
		 * @since 0.1.0
		 *
		 * @param array $user_ids The array of blocked user ids.
		 */
		return (array) apply_filters( 'bpbu_get_blocked_user_ids', $user_ids );
	}

	/**
	 * Returns an array of blocked user `WP_User` objects.
	 *
	 * This function is a wrapper for `WP_User_Query` that only returns results for
	 * users that are blocked. Any ids passed in the `includes` parameter that don't
	 * belong to a blocked user will be filtered out.
	 *
	 * @since 0.2.0
	 *
	 * @param array $args Arguments to pass to `WP_User_Query`.
	 *
	 * @return WP_User_Query
	 */
	public static function get_blocked_users( $args = array() ) {

		// Get the blocked user ids.
		$user_ids = BPBU_User::get_blocked_user_ids();

		// Set the default user query args.
		$r = array();

		// Set query vars if we have blocked users.
		if ( ! empty( $user_ids ) ) {

			// Make sure we have an array.
			$r = bp_parse_args( $args, array(), 'bpbu_get_blocked_users' );

			// Set the `includes` parameter to only get our blocked user objects.
			if ( ! empty( $r['include'] ) ) {
				$r['include'] = array_intersect( (array) $r['include'], $user_ids );
			} else {
				$r['include'] = $user_ids;
			}
		}

		// Run the user query.
		$query = new WP_User_Query( $r );

		/**
		 * Filters the return of the blocked user objects array.
		 *
		 * @since 0.2.0
		 *
		 * @param WP_User_Query $query The WP_User_Query object.
		 */
		return apply_filters( 'bpbu_get_blocked_users', $query );
	}

	/**
	 * Destroys all the user sessions for the specified user.
	 *
	 * @since 0.2.0
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
		$manager = WP_Session_Tokens::get_instance( $user_id );
		$manager->destroy_all();
	}
}
