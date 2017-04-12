<?php
/**
 * BP Block Users tests for deprecated functions.
 *
 * @since 1.0.0
 *
 * @group functions
 * @group deprecated
 *
 * @package BP_Block_Users
 * @subpackage Tests
 */

/**
 * BP Block Users tests for deprecated functions.
 */
class BPBU_Tests_Deprecated extends BP_UnitTestCase {

	/**
	 * The test user.
	 *
	 * @var int
	 */
	private static $user_id = 0;

	/**
	 * Set up the test user.
	 */
	public static function setUpBeforeClass() {
		$f = new BP_UnitTest_Factory();
		self::$user_id = $f->user->create( array(
			'user_login' => 'deprecated_user',
			'user_email' => 'deprecated_user@example.com',
		) );
		self::commit_transaction();
	}

	/**
	 * Delete the test user.
	 */
	public static function tearDownAfterClass() {
		wp_delete_user( self::$user_id );
		self::commit_transaction();
	}

	/**
	 * Reset blocked user data after each test.
	 */
	public function tearDown() {
		parent::tearDown();
		bp_delete_user_meta( self::$user_id, 'bpbu_user_blocked' );
		bp_delete_user_meta( self::$user_id, 'bpbu_user_blocked_expiration' );
	}

	/* Admin ******************************************************************/

	/**
	 * Test for `tba_bp_block_users_settings_fields()`
	 *
	 * @since 1.0.0
	 *
	 * @covers ::tba_bp_block_users_settings_fields
	 */
	public function test_tba_bp_block_users_settings_fields() {
		$this->markTestIncomplete();
	}

	/**
	 * Test for `tba_bp_block_users_update_user_settings()`
	 *
	 * @since 1.0.0
	 *
	 * @covers ::tba_bp_block_users_update_user_settings
	 */
	public function test_tba_bp_block_users_update_user_settings() {
		$this->markTestIncomplete();
	}

	/**
	 * Test for `tba_bp_block_users_row_actions()`
	 *
	 * @since 1.0.0
	 *
	 * @covers ::tba_bp_block_users_row_actions
	 */
	public function test_tba_bp_block_users_row_actions() {
		$this->markTestIncomplete();
	}

	/* User Functions *********************************************************/

	/**
	 * Test for `tba_bp_block_user()`
	 *
	 * @since 1.0.0
	 *
	 * @TODO Replace assertTrue( false !== $var ) with assertNotFalse( $var )
	 *       when PHP 5.2 support is dropped.
	 *
	 * @covers ::tba_bp_block_user
	 * @expectedDeprecated tba_bp_block_user
	 */
	public function test_tba_bp_block_user() {

		// Returns false when no user id is passed.
		$this->assertFalse( tba_bp_block_user() );

		$is_blocked      = tba_bp_block_user( self::$user_id );
		$blocked_meta    = bp_get_user_meta( self::$user_id, 'bpbu_user_blocked', true );
		$expiration_meta = bp_get_user_meta( self::$user_id, 'bpbu_user_blocked_expiration', true );
		$this->assertTrue( false !== $is_blocked );
		$this->assertEquals( '1', $blocked_meta );
		$this->assertEquals( '3000-01-01 00:00:00', $expiration_meta );

		$now             = current_time( 'timestamp', 1 );
		$expiration      = $now + ( 3 * MINUTE_IN_SECONDS );
		$is_blocked      = tba_bp_block_user( self::$user_id, 3, 'minutes' );
		$blocked_meta    = bp_get_user_meta( self::$user_id, 'bpbu_user_blocked', true );
		$expiration_meta = bp_get_user_meta( self::$user_id, 'bpbu_user_blocked_expiration', true );
		$this->assertTrue( false !== $is_blocked );
		$this->assertEquals( '1', $blocked_meta );
		$this->assertEquals( gmdate( 'Y-m-d H:i:s', $expiration ), $expiration_meta );
	}

	/**
	 * Test for `tba_bp_unblock_user()`
	 *
	 * @since 1.0.0
	 *
	 * @covers ::tba_bp_unblock_user
	 * @expectedDeprecated tba_bp_unblock_user
	 */
	public function test_tba_bp_unblock_user() {

		// Returns false when no user id is passed.
		$this->assertFalse( tba_bp_unblock_user() );

		// Block the user.
		$is_blocked      = BPBU_User::block( self::$user_id );
		$blocked_meta    = bp_get_user_meta( self::$user_id, 'bpbu_user_blocked', true );
		$expiration_meta = bp_get_user_meta( self::$user_id, 'bpbu_user_blocked_expiration', true );
		$this->assertTrue( $is_blocked );
		$this->assertEquals( '1', $blocked_meta );
		$this->assertEquals( '3000-01-01 00:00:00', $expiration_meta );

		$is_unblocked    = tba_bp_unblock_user( self::$user_id );
		$blocked_meta    = bp_get_user_meta( self::$user_id, 'bpbu_user_blocked', true );
		$expiration_meta = bp_get_user_meta( self::$user_id, 'bpbu_user_blocked_expiration', true );
		$this->assertTrue( $is_unblocked );
		$this->assertEmpty( $blocked_meta );
		$this->assertEmpty( $expiration_meta );
	}

	/**
	 * Test for `tba_bp_update_blocked_user_expiration()`
	 *
	 * @since 1.0.0
	 *
	 * @TODO Replace assertTrue( false !== $var ) with assertNotFalse( $var )
	 *       when PHP 5.2 support is dropped.
	 *
	 * @covers ::tba_bp_update_blocked_user_expiration
	 * @expectedDeprecated tba_bp_update_blocked_user_expiration
	 */
	public function test_tba_bp_update_blocked_user_expiration() {

		$this->assertFalse( tba_bp_update_blocked_user_expiration() );

		$updated = tba_bp_update_blocked_user_expiration( self::$user_id );
		$meta    = bp_get_user_meta( self::$user_id, 'bpbu_user_blocked_expiration', true );
		$this->assertTrue( false !== $updated );
		$this->assertEquals( '3000-01-01 00:00:00', $meta );

		$now        = current_time( 'timestamp', 1 );
		$expiration = $now + ( 3 * MINUTE_IN_SECONDS );
		$updated    = tba_bp_update_blocked_user_expiration( self::$user_id, 3, 'minutes' );
		$meta       = bp_get_user_meta( self::$user_id, 'bpbu_user_blocked_expiration', true );
		$this->assertTrue( false !== $updated );
		$this->assertEquals( gmdate( 'Y-m-d H:i:s', $expiration ), $meta );
	}

	/**
	 * Test for `tba_bp_get_blocked_user_expiration()`
	 *
	 * @since 1.0.0
	 *
	 * @covers ::tba_bp_get_blocked_user_expiration
	 * @expectedDeprecated tba_bp_get_blocked_user_expiration
	 */
	public function test_tba_bp_get_blocked_user_expiration() {

		// False when no user id is passed.
		$this->assertFalse( tba_bp_get_blocked_user_expiration() );

		$this->assertEquals( '3000-01-01 00:00:00', tba_bp_get_blocked_user_expiration( self::$user_id ) );
		$this->assertEquals( 32503680000, tba_bp_get_blocked_user_expiration( self::$user_id, true ) );

		BPBU_User::block( self::$user_id );
		$this->assertEquals( '3000-01-01 00:00:00', tba_bp_get_blocked_user_expiration( self::$user_id ) );
		$this->assertEquals( 32503680000, tba_bp_get_blocked_user_expiration( self::$user_id, true ) );

		$now            = current_time( 'timestamp', 1 );
		$expiration     = gmdate( 'Y-m-d H:i:s', ( $now + ( 3 * MINUTE_IN_SECONDS ) ) );
		$expiration_int = $now + ( 3 * MINUTE_IN_SECONDS );
		BPBU_User::block( self::$user_id, 3, 'minutes' );
		$this->assertEquals( $expiration, tba_bp_get_blocked_user_expiration( self::$user_id ) );
		$this->assertEquals( $expiration_int, tba_bp_get_blocked_user_expiration( self::$user_id, true ) );
	}

	/**
	 * Test for `tba_bp_is_user_blocked()`
	 *
	 * @since 1.0.0
	 *
	 * @covers ::tba_bp_is_user_blocked
	 * @expectedDeprecated tba_bp_is_user_blocked
	 */
	public function test_tba_bp_is_user_blocked() {

		// Returns false when no user id is passed.
		$this->assertFalse( tba_bp_is_user_blocked() );

		BPBU_User::block( self::$user_id );
		$this->assertTrue( tba_bp_is_user_blocked( self::$user_id ) );

		BPBU_User::unblock( self::$user_id );
		$this->assertFalse( tba_bp_is_user_blocked( self::$user_id ) );
	}

	/**
	 * Test for `tba_bp_get_blocked_user_ids()`
	 *
	 * @since 1.0.0
	 *
	 * @covers ::tba_bp_get_blocked_user_ids
	 * @expectedDeprecated tba_bp_get_blocked_user_ids
	 */
	public function test_tba_bp_get_blocked_user_ids() {

		// Returns false when no user id is passed.
		$this->assertSame( array(), tba_bp_get_blocked_user_ids() );

		$users = $this->factory->user->create_many( 3 );

		BPBU_User::block( $users[0] );
		BPBU_User::block( $users[1] );
		$this->assertEqualSets( array( $users[0], $users[1] ), tba_bp_get_blocked_user_ids() );

		bp_update_user_meta( $users[1], 'bpbu_user_blocked_expiration', gmdate( 'Y-m-d H:i:s', ( time() - MINUTE_IN_SECONDS ) ) );
		$this->assertEqualSets( array( $users[0] ), tba_bp_get_blocked_user_ids() );
	}

	/* Notification Emails ****************************************************/

	/**
	 * Test for `tba_bp_block_users_block_notifications()`
	 *
	 * @since 1.0.0
	 *
	 * @covers ::tba_bp_block_users_block_notifications
	 * @expectedDeprecated tba_bp_block_users_block_notifications
	 */
	public function test_tba_bp_block_users_block_notifications() {

		$meta_key = 'notification_activity_new_mention';

		// The user id or meta key are empty.
		$this->assertEquals( 'yes', tba_bp_block_users_block_notifications( 'yes', 0, $meta_key, true ) );
		$this->assertEquals( 'yes', tba_bp_block_users_block_notifications( 'yes', self::$user_id, '', true ) );

		$this->assertEquals( 'yes', tba_bp_block_users_block_notifications( 'yes', self::$user_id, 'test_meta_key', true ) );

		BPBU_User::block( self::$user_id );
		$this->assertEquals( 'no', tba_bp_block_users_block_notifications( 'yes', self::$user_id, $meta_key, true ) );

		BPBU_User::unblock( self::$user_id );
		$this->assertEquals( 'yes', tba_bp_block_users_block_notifications( 'yes', self::$user_id, $meta_key, true ) );
	}

	/* Authentication *********************************************************/

	/**
	 * Test for `tba_bp_prevent_blocked_user_login()`.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::tba_bp_prevent_blocked_user_login
	 * @expectedDeprecated tba_bp_prevent_blocked_user_login
	 */
	public function test_tba_bp_prevent_blocked_user_login() {

		$user = tba_bp_prevent_blocked_user_login( new WP_User() );
		$this->assertEquals( 0, $user->ID );

		$userdata = new WP_User( self::$user_id );

		$user = tba_bp_prevent_blocked_user_login( $userdata );
		$this->assertEquals( self::$user_id, $user->ID );

		BPBU_User::block( self::$user_id );
		$user             = tba_bp_prevent_blocked_user_login( $userdata );
		$expected_message = 'This account has been blocked.';
		$actual_message   = $user->get_error_message( 'bpbu_authentication_blocked' );
		$this->assertWPError( $user );
		$this->assertContains( $expected_message, $actual_message );

		BPBU_User::block( self::$user_id, 3, 'minutes' );
		$user             = tba_bp_prevent_blocked_user_login( $userdata );
		$expected_message = 'This account has been temporarily blocked.';
		$actual_message   = $user->get_error_message( 'bpbu_authentication_blocked' );
		$this->assertWPError( $user );
		$this->assertContains( $expected_message, $actual_message );
	}

	/* Sub-nav/Admin Bar Menus ************************************************/

	/**
	 * Test for `tba_bp_block_user_settings_sub_nav()`.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::tba_bp_block_user_settings_sub_nav
	 */
	public function test_tba_bp_block_user_settings_sub_nav() {
		$this->markTestIncomplete();
	}

	/**
	 * Test for `tba_bp_block_users_admin_bar_admin_menu()`.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::tba_bp_block_users_admin_bar_admin_menu
	 */
	public function test_tba_bp_block_users_admin_bar_admin_menu() {
		$this->markTestIncomplete();
	}

	/* Settings Actions *******************************************************/

	/**
	 * Test for `tba_bp_settings_action_block_user()`.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::tba_bp_settings_action_block_user
	 */
	public function test_tba_bp_settings_action_block_user() {
		$this->markTestIncomplete();
	}

	/* Template ***************************************************************/

	/**
	 * Test for `tba_bp_block_user_settings_message()`.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::tba_bp_block_user_settings_message
	 */
	public function test_tba_bp_block_user_settings_message() {
		$this->markTestIncomplete();
	}

	/**
	 * Test for `tba_bp_get_block_user_settings_message()`.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::tba_bp_get_block_user_settings_message
	 */
	public function test_tba_bp_get_block_user_settings_message() {
		$this->markTestIncomplete();
	}

	/**
	 * Test for `tba_bp_block_users_show_settings_message()`.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::tba_bp_block_users_show_settings_message
	 */
	public function test_tba_bp_block_users_show_settings_message() {
		$this->markTestIncomplete();
	}

	/* Theme Compat ***********************************************************/

	/**
	 * Test for `tba_bp_block_user_settings_load_template_filter()`.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::tba_bp_block_user_settings_load_template_filter
	 */
	public function test_tba_bp_block_user_settings_load_template_filter() {
		$this->markTestIncomplete();
	}

	/**
	 * Test for `tba_bp_block_user_get_template_directory()`.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::tba_bp_block_user_get_template_directory
	 */
	public function test_tba_bp_block_user_get_template_directory() {
		$this->markTestIncomplete();
	}

	/**
	 * Test for `tba_bp_settings_screen_block_user()`.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::tba_bp_settings_screen_block_user
	 */
	public function test_tba_bp_settings_screen_block_user() {
		$this->markTestIncomplete();
	}
}
