<?php
/**
 * BP Block Users CLI commands.
 *
 * @package BP_Block_Users
 * @subpackage WP_CLI
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Bail if WP_CLI isn't loaded.
if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * The BP Block Users WP-CLI commands.
 *
 * @since 1.0.0
 */
class BPBU_CLI extends WP_CLI_Command {

	private $fields = array(
		'ID',
		'user_login',
		'expiration',
	);

	public function __construct() {
		$this->fetcher = new \WP_CLI\Fetchers\User;
	}

	/**
	 * List users that are blocked.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - count
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp-block-users list
	 *     +----------------------+---------------------+
	 *     | ID  | user_login     | expiration          |
	 *     +----------------------+---------------------+
	 *     | 3   | user_login_3   | 2017-05-02 12:23:56 |
	 *     | 23  | user_login_23  | indefinitely        |
	 *     | 123 | user_login_123 | 2017-08-03 21:18:32 |
	 *     +----------------------+---------------------+
	 *
	 * @subcommand list
	 */
	public function _list( $_, $assoc_args ) {
		$blocked_users = $this->fetcher->get_many( BPBU_User::get_blocked_user_ids() );

		$output_users = array();
		foreach ( $blocked_users as $user ) {

			$expiration = BPBU_User::get_expiration( $user->ID );
			if ( '3000-01-01 00:00:00' === $expiration ) {
				$expiration = 'indefinitely';
			}

			$output_user = new stdClass;

			$output_user->ID         = $user->ID;
			$output_user->user_login = $user->user_login;
			$output_user->expiration = $expiration;

			$output_users[] = $output_user;
		}
		$formatter = new \WP_CLI\Formatter( $assoc_args, $this->fields );
		$formatter->display_items( $output_users );
	}

	/**
	 * Blocks one or more users.
	 *
	 * ## OPTIONS
	 *
	 * <user>...
	 * : One or more user IDs, user emails, or user logins.
	 *
	 * [--length=<length>]
	 * : The length of time units to block a user.
	 * ---
	 * default: 0
	 * ---
	 *
	 * [--unit=<unit>]
	 * : The unit of time to block a user.
	 * ---
	 * default: indefintely
	 * options:
	 *   - minutes
	 *   - hours
	 *   - days
	 *   - months
	 *   - indefintely
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp-block-users block 123 user_login
	 *     Success: Blocked 2 of 2 users indefinitely.
	 *
	 *     $ wp bp-block-users block 123 --length=3 --unit=days
	 *     Success: Blocked user for 3 days.
	 *
	 * @when after_wp_load
	 */
	public function block( $args, $assoc_args ) {

		// Get the users.
		$users      = $this->fetcher->get_many( $args );
		$user_count = count( $args );

		// Set some default variables.
		$successes = 0;

		// Get the user ids.
		$user_ids = wp_list_pluck( $users, 'ID' );

		// Get the length of time to block.
		$length = 0;
		if ( is_numeric( $assoc_args['length'] ) ) {
			$length = (int) $assoc_args['length'];
		}

		$units = array( 'minutes', 'hours', 'days', 'months' );

		// Get the unit of time to block.
		$unit = 'indefintely';
		if ( in_array( $assoc_args['unit'], $units, true ) ) {
			$unit = $assoc_args['unit'];
		}

		$length_message = 'indefintely';
		if ( 0 !== $length && 'indefintely' !== $unit ) {
			$unit = ( 1 === $length ) ? trim( $unit, 's' ) : $unit;
			$length_message = "for {$length} {$unit}";
		}

		foreach ( $users as $user ) {
			if ( is_super_admin( $user->ID ) ) {
				continue;
			}

			if ( false !== BPBU_User::block( $user->ID, $length, $unit ) ) {
				$successes++;
			}
		}

		if ( $successes === $user_count ) {
			$user_message = $successes > 1 ? 'users' : 'user';
			WP_CLI::success( "Blocked {$successes} {$user_message} {$length_message}." );
		} elseif ( 0 < $successes ) {
			WP_CLI::error( "Only blocked {$successes} of {$user_count} users {$length_message}." );
		} else {
			WP_CLI::error( 'No users were blocked.' );
		}
	}

	/**
	 * Unblocks one or more users.
	 *
	 * ## OPTIONS
	 *
	 * [<user>...]
	 * : One or more user IDs, user emails, or user logins.
	 *
	 * [--all]
	 * : If set, all blocked users will be unblocked.
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp-block-users unblock 123
	 *     Success: Unblocked 1 user.
	 *
	 *     $ wp bp-block-users unblock --all
	 *     Success: Unblocked 3 users.
	 *
	 * @when after_wp_load
	 */
	public function unblock( $args, $assoc_args ) {

		if ( \WP_CLI\Utils\get_flag_value( $assoc_args, 'all' ) ) {
			$args = BPBU_User::get_blocked_user_ids();
		}

		// Get the users.
		$users      = $this->fetcher->get_many( $args );
		$user_count = count( $args );

		// Set some default variables.
		$successes = 0;

		// Get the user ids.
		$user_ids = wp_list_pluck( $users, 'ID' );

		foreach ( $users as $user ) {

			if ( false !== BPBU_User::unblock( $user->ID ) ) {
				$successes++;
			}
		}

		if ( $successes === $user_count ) {
			$user_message = $successes > 1 ? 'users' : 'user';
			WP_CLI::success( "Unblocked {$successes} {$user_message}." );
		} elseif ( 0 < $successes ) {
			WP_CLI::error( "Only unblocked {$successes} of {$user_count} users." );
		} else {
			WP_CLI::error( 'No users were unblocked.' );
		}
	}
}
WP_CLI::add_command( 'bp-block-users', 'BPBU_CLI' );
