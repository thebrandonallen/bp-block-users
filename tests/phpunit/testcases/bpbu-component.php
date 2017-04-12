<?php
/**
 * Test template functions.
 *
 * @group component
 *
 * @package BP_Block_Users
 * @subpackage Tests
 */

/**
 * BPBU_Component test class.
 */
class BPBU_Tests_BPBU_Component extends BP_UnitTestCase {

	/**
	 * Test `BPBU_Component::prevent_blocked_user_login()`.
	 *
	 * @since 1.0.0
	 *
	 * @covers BPBU_Component::prevent_blocked_user_login
	 */
	public function test_prevent_blocked_user_login() {

		$u = $this->factory->user->create();

		$bpbu_component = buddypress()->block_users;

		$user = $bpbu_component->prevent_blocked_user_login( new WP_User() );
		$this->assertEquals( 0, $user->ID );

		$userdata = new WP_User( $u );

		$user = $bpbu_component->prevent_blocked_user_login( $userdata );
		$this->assertEquals( $u, $user->ID );

		BPBU_User::block( $u );
		$user             = $bpbu_component->prevent_blocked_user_login( $userdata );
		$expected_message = 'This account has been blocked.';
		$actual_message   = $user->get_error_message( 'bpbu_authentication_blocked' );
		$this->assertWPError( $user );
		$this->assertContains( $expected_message, $actual_message );

		BPBU_User::block( $u, 3, 'minutes' );
		$user             = $bpbu_component->prevent_blocked_user_login( $userdata );
		$expected_message = 'This account has been temporarily blocked.';
		$actual_message   = $user->get_error_message( 'bpbu_authentication_blocked' );
		$this->assertWPError( $user );
		$this->assertContains( $expected_message, $actual_message );
	}

	/**
	 * Test `BPBU_Component::filter_deprecated_meta_keys()`.
	 *
	 * @since 1.0.0
	 *
	 * @covers BPBU_Component::filter_deprecated_meta_keys
	 * @expectedIncorrectUsage tba_bp_user_blocked
	 * @expectedIncorrectUsage tba_bp_user_blocked_expiration
	 */
	public function test_filter_deprecated_meta_keys() {

		$u = $this->factory->user->create();

		$bpbu_component = buddypress()->block_users;

		$this->assertNull( $bpbu_component->filter_deprecated_meta_keys( null, $u, 'test_meta_key', true ) );

		BPBU_User::block( $u );
		$blocked_meta    = $bpbu_component->filter_deprecated_meta_keys( null, $u, 'tba_bp_user_blocked', true );
		$expiration_meta = $bpbu_component->filter_deprecated_meta_keys( null, $u, 'tba_bp_user_blocked_expiration', true );
		$this->assertEquals( '1', $blocked_meta );
		$this->assertEquals( '3000-01-01 00:00:00', $expiration_meta );
	}
}
