<?php
/**
 * Test template functions.
 *
 * @group template
 *
 * @package BP_Block_Users
 * @subpackage Tests
 */

/**
 * Template functions test class.
 */
class BPBU_Tests_Template extends BP_UnitTestCase {

	/**
	 * Test `bpbu_block_user_settings_message()`.`
	 *
	 * @since 0.2.0
	 *
	 * @covers ::bpbu_block_user_settings_message
	 */
	public function test_bpbu_block_user_settings_message() {

		$this->expectOutputString( 'This member is not currently blocked.' );
		bpbu_block_user_settings_message();
	}

	/**
	 * Test `bpbu_get_block_user_settings_message()`.`
	 *
	 * @since 0.2.0
	 *
	 * @covers ::bpbu_get_block_user_settings_message
	 */
	public function test_bpbu_get_block_user_settings_message() {

		// Test the default message.
		$message = bpbu_get_block_user_settings_message();
		$this->assertEquals( 'This member is not currently blocked.', $message );
	}

	/**
	 * Test `bpbu_block_users_show_settings_message()`.`
	 *
	 * @since 0.2.0
	 *
	 * @covers ::bpbu_block_users_show_settings_message
	 */
	public function test_bpbu_block_users_show_settings_message() {

		$this->expectOutputString( '' );
		bpbu_block_users_show_settings_message();

		ob_clean();

		buddypress()->current_action = 'block-user';
		ob_start();
		bpbu_block_users_show_settings_message();
		$content = ob_get_clean();
		$this->assertContains( 'This member is not currently blocked.', $content );
	}
}
