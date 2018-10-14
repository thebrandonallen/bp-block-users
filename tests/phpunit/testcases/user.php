<?php
/**
 * Tests for BPBU_User.
 *
 * @package BP_Block_Users
 * @subpackage Tests
 */

/**
 * The BPBU_Tests_User test class.
 *
 * @since 1.0.0
 *
 * @group functions
 * @group users
 */
class BPBU_Tests_BPBU_User extends BP_UnitTestCase {

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
		$f             = new BP_UnitTest_Factory();
		self::$user_id = $f->user->create(
			array(
				'user_login' => 'class_bpbu_user',
				'user_email' => 'class_bpbu_user@example.com',
			)
		);
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

	/**
	 * Test `BPBU_User::block()`.
	 *
	 * @since 1.0.0
	 *
	 * @TODO Replace assertTrue( false !== $var ) with assertNotFalse( $var )
	 *       when PHP 5.2 support is dropped.
	 *
	 * @covers BPBU_User::block
	 */
	public function test_block() {

		// Returns false when no user id is passed.
		$this->assertFalse( BPBU_User::block() );

		$is_blocked      = BPBU_User::block( self::$user_id );
		$blocked_meta    = bp_get_user_meta( self::$user_id, 'bpbu_user_blocked', true );
		$expiration_meta = bp_get_user_meta( self::$user_id, 'bpbu_user_blocked_expiration', true );
		$this->assertTrue( false !== $is_blocked );
		$this->assertEquals( '1', $blocked_meta );
		$this->assertEquals( '3000-01-01 00:00:00', $expiration_meta );

		$now             = current_time( 'timestamp', 1 );
		$expiration      = $now + ( 3 * MINUTE_IN_SECONDS );
		$is_blocked      = BPBU_User::block( self::$user_id, 3, 'minutes' );
		$blocked_meta    = bp_get_user_meta( self::$user_id, 'bpbu_user_blocked', true );
		$expiration_meta = bp_get_user_meta( self::$user_id, 'bpbu_user_blocked_expiration', true );
		$this->assertTrue( false !== $is_blocked );
		$this->assertEquals( '1', $blocked_meta );
		$this->assertEquals( gmdate( 'Y-m-d H:i:s', $expiration ), $expiration_meta );
	}

	/**
	 * Test `BPBU_User::unblock()`.
	 *
	 * @since 1.0.0
	 *
	 * @covers BPBU_User::unblock
	 */
	public function test_unblock() {

		// Returns false when no user id is passed.
		$this->assertFalse( BPBU_User::unblock() );

		// Block the user.
		$is_blocked      = BPBU_User::block( self::$user_id );
		$blocked_meta    = bp_get_user_meta( self::$user_id, 'bpbu_user_blocked', true );
		$expiration_meta = bp_get_user_meta( self::$user_id, 'bpbu_user_blocked_expiration', true );
		$this->assertTrue( $is_blocked );
		$this->assertEquals( '1', $blocked_meta );
		$this->assertEmpty( '0', $expiration_meta );

		$is_unblocked    = BPBU_User::unblock( self::$user_id );
		$blocked_meta    = bp_get_user_meta( self::$user_id, 'bpbu_user_blocked', true );
		$expiration_meta = bp_get_user_meta( self::$user_id, 'bpbu_user_blocked_expiration', true );
		$this->assertTrue( $is_unblocked );
		$this->assertEmpty( $blocked_meta );
		$this->assertEmpty( $expiration_meta );
	}

	/**
	 * Test `BPBU_User::update_expiration()`.
	 *
	 * @since 1.0.0
	 *
	 * @TODO Replace assertTrue( false !== $var ) with assertNotFalse( $var )
	 *       when PHP 5.2 support is dropped.
	 *
	 * @covers BPBU_User::update_expiration
	 */
	public function test_update_expiration() {

		$this->assertFalse( BPBU_User::update_expiration() );

		$updated = BPBU_User::update_expiration( self::$user_id );
		$meta    = bp_get_user_meta( self::$user_id, 'bpbu_user_blocked_expiration', true );
		$this->assertTrue( false !== $updated );
		$this->assertEquals( '3000-01-01 00:00:00', $meta );

		$now        = current_time( 'timestamp', 1 );
		$expiration = $now + ( 3 * MINUTE_IN_SECONDS );
		$updated    = BPBU_User::update_expiration( self::$user_id, 3, 'minutes' );
		$meta       = bp_get_user_meta( self::$user_id, 'bpbu_user_blocked_expiration', true );
		$this->assertTrue( false !== $updated );
		$this->assertEquals( gmdate( 'Y-m-d H:i:s', $expiration ), $meta );
	}

	/**
	 * Test `BPBU_User::get_expiration()`.
	 *
	 * @since 1.0.0
	 *
	 * @covers BPBU_User::get_expiration
	 */
	public function test_get_expiration() {

		// False when no user id is passed.
		$this->assertFalse( BPBU_User::get_expiration() );

		$this->assertEquals( '3000-01-01 00:00:00', BPBU_User::get_expiration( self::$user_id ) );

		BPBU_User::block( self::$user_id );
		$this->assertEquals( '3000-01-01 00:00:00', BPBU_User::get_expiration( self::$user_id ) );

		$now            = current_time( 'timestamp', 1 );
		$expiration     = gmdate( 'Y-m-d H:i:s', ( $now + ( 3 * MINUTE_IN_SECONDS ) ) );
		$expiration_int = $now + ( 3 * MINUTE_IN_SECONDS );
		BPBU_User::block( self::$user_id, 3, 'minutes' );
		$this->assertEquals( $expiration, BPBU_User::get_expiration( self::$user_id ) );
	}

	/**
	 * Test `BPBU_User::is_blocked()`.
	 *
	 * @since 1.0.0
	 *
	 * @covers BPBU_User::is_blocked
	 */
	public function test_is_blocked() {

		// Returns false when no user id is passed.
		$this->assertFalse( BPBU_User::is_blocked() );

		BPBU_User::block( self::$user_id );
		$this->assertTrue( BPBU_User::is_blocked( self::$user_id ) );

		BPBU_User::unblock( self::$user_id );
		$this->assertFalse( BPBU_User::is_blocked( self::$user_id ) );
	}

	/**
	 * Test `BPBU_User::get_blocked_user_ids()`.
	 *
	 * @since 1.0.0
	 *
	 * @covers BPBU_User::get_blocked_user_ids
	 */
	public function test_get_blocked_user_ids() {

		// Returns false when no user id is passed.
		$this->assertSame( array(), BPBU_User::get_blocked_user_ids() );

		$users = $this->factory->user->create_many( 3 );

		BPBU_User::block( $users[0] );
		BPBU_User::block( $users[1] );
		$this->assertEqualSets( array( $users[0], $users[1] ), BPBU_User::get_blocked_user_ids() );

		bp_update_user_meta( $users[1], 'bpbu_user_blocked_expiration', gmdate( 'Y-m-d H:i:s', ( time() - MINUTE_IN_SECONDS ) ) );
		$this->assertEqualSets( array( $users[0] ), BPBU_User::get_blocked_user_ids() );
	}

	/**
	 * Test `BPBU_User::destroy_sessions()`.
	 *
	 * @since 1.0.0
	 *
	 * @covers BPBU_User::destroy_sessions
	 */
	public function test_destroy_sessions() {

		// Make sure sessions meta exists.
		update_user_meta( self::$user_id, 'session_tokens', 'sessions' );

		BPBU_User::destroy_sessions( self::$user_id );

		$this->assertEquals( array(), get_user_meta( self::$user_id, 'session_tokens' ) );
	}
}
