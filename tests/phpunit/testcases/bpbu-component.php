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
	 * @since 0.2.0
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
}
