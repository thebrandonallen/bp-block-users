<?php

/** This action is documented in bp-templates/bp-legacy/buddypress/members/single/settings/profile.php */
do_action( 'bp_before_member_settings_template' ); ?>

<form action="<?php echo bp_displayed_user_domain() . bp_get_settings_slug() . '/block-user/'; ?>" name="account-block-user-form" id="account-block-user-form" class="standard-form" method="post">

	<?php

	/**
	 * Fires before the display of the submit button for user capabilities saving.
	 *
	 * @since BuddyPress (1.6.0)
	 */
	do_action( 'bp_members_block_user_before_submit' ); ?>

	<label for="block-user">
		<input type="checkbox" name="block-user" id="block-user" value="1" <?php checked( BPBU_User::is_blocked( bp_displayed_user_id() ) ); ?> />
		 <?php esc_html_e( 'Block this member?', 'bp-block-users' ); ?>
	</label>

	<label><?php esc_html_e( 'Length of time member should be blocked.', 'bp-block-users' ); ?></label>

	<label for="block-user-length" class="bp-screen-reader-text"><?php esc_html_e( 'Numeric length of time member should be blocked.', 'bp-block-users' ); ?></label>
	<input type="text" name="block-user-length" id="block-user-length" value="0" style="width: 25%" />

	<label for="block-user-unit" class="bp-screen-reader-text"><?php esc_html_e( 'Unit of time a member should be blocked.', 'bp-block-users' ); ?></label>
	<select name="block-user-unit" id="block-user-unit">
		<option value="minutes"><?php esc_html_e( 'minute(s)', 'bp-block-users' ); ?></option>
		<option value="hours"><?php esc_html_e( 'hour(s)', 'bp-block-users' ); ?></option>
		<option value="days"><?php esc_html_e( 'day(s)', 'bp-block-users' ); ?></option>
		<option value="weeks"><?php esc_html_e( 'week(s)', 'bp-block-users' ); ?></option>
		<option value="months"><?php esc_html_e( 'month(s)', 'bp-block-users' ); ?></option>
		<option value="indefinitely" selected="selected"><?php esc_html_e( 'Indefinitely', 'bp-block-users' ); ?></option>
	</select>

	<p><em><?php __( 'If "Indefinitely" is chosen, the time-length field will be ignored.', 'bp-block-users' ); ?></em></p>

	<div class="submit">
		<input type="submit" value="<?php esc_attr_e( 'Save', 'bp-block-users' ); ?>" id="block-user-submit" name="block-user-submit" />
	</div>

	<?php

	/**
	 * Fires after the display of the submit button for user capabilities saving.
	 *
	 * @since BuddyPress (1.6.0)
	 */
	do_action( 'bp_members_block_user_after_submit' ); ?>

	<?php wp_nonce_field( 'block-user' ); ?>

</form>

<?php

/** This action is documented in bp-templates/bp-legacy/buddypress/members/single/settings/profile.php */
do_action( 'bp_after_member_settings_template' ); ?>
