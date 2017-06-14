<?php
/**
 * BP Block Users Component class.
 *
 * @package BP_Block_Users
 * @subpackage Component
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( class_exists( 'BP_Component' ) ) {

	/**
	 * The BP Block Users Component Class.
	 *
	 * @since 1.0.0
	 */
	class BPBU_Component extends BP_Component {

		/**
		 * The plugin version.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		const VERSION = '1.0.1';

		/**
		 * The database version.
		 *
		 * @since 1.0.0
		 *
		 * @var int
		 */
		const DB_VERSION = 20;

		/**
		 * The path to BP Block Users includes.
		 *
		 * @since 1.0.0
		 *
		 * @var string $includes_dir
		 */
		public $includes_dir = '';

		/**
		 * The path to BP Block Users classes.
		 *
		 * @since 1.0.0
		 *
		 * @var string $classes_dir
		 */
		public $classes_dir = '';

		/* Methods ************************************************************/

		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 *
		 * @param string $file The main BP Block Users file.
		 */
		public function __construct( $file = '' ) {

			// Let's start the show!
			parent::start(
				'block_users',
				__( 'Block Users', 'bp-block-users' ),
				plugin_dir_path( $file ),
				array()
			);

			// Extra directory properties.
			$this->includes_dir = $this->path . 'includes/';
			$this->classes_dir  = $this->path . 'classes/';

			// Include our files.
			$this->includes();

			// Maybe load the admin.
			$this->load_admin();

			// Setup actions.
			$this->setup_actions();

			// Register BP Block Users as an active component.
			buddypress()->active_components[ $this->id ] = '1';
		}

		/**
		 * Include required files.
		 *
		 * @since 1.0.0
		 *
		 * @param array $includes An array of file names, or file name chunks,
		 *                        to be parsed and then included.
		 *
		 * @return void
		 */
		public function includes( $includes = array() ) {

			// Classes.
			require $this->classes_dir . 'class-bpbu-template-stack.php';
			require $this->classes_dir . 'class-bpbu-user.php';

			// Includes.
			require $this->includes_dir . 'deprecated.php';
			require $this->includes_dir . 'helpers.php';
			require $this->includes_dir . 'template.php';

			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				require $this->classes_dir . 'class-bpbu-cli.php';
			}

			parent::includes( $includes );
		}

		/**
		 * Loads and initializes the admin when needed.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		private function load_admin() {
			if ( ! is_admin() ) {
				return;
			}

			require $this->classes_dir . 'class-bpbu-admin.php';
			require $this->classes_dir . 'class-bpbu-admin-list-tables.php';
			require $this->classes_dir . 'class-bpbu-admin-profile.php';

			add_action( 'bp_init', 'BPBU_Admin::get_instance' );
		}

		/**
		 * Set up the actions.
		 *
		 * @since 1.0.0
		 */
		public function setup_actions() {

			/* Filters ********************************************************/

			// Set all notification emails to "no".
			add_filter( 'get_user_metadata', array( $this, 'block_notifications' ), 10, 4 );

			// Add the BP Block Users template to template stack.
			add_filter( 'bp_located_template', 'BPBU_Template_Stack::settings_load_template_filter', 10, 2 );

			// Prevent the login of a blocked user.
			add_filter( 'authenticate', array( $this, 'prevent_blocked_user_login' ), 40 );

			// Filter for our deprecated meta keys.
			add_filter( 'get_user_metadata', array( $this, 'filter_deprecated_meta_keys' ), 10, 4 );

			/* Actions ********************************************************/

			// Add block user settings sub nav.
			add_action( 'bp_settings_setup_nav', array( $this, 'setup_settings_sub_nav' ) );

			// Add the our admin bar link.
			add_action( 'admin_bar_menu', array( $this, 'setup_settings_admin_bar' ), 100 );

			// Block/unblock user when editing from profile.
			add_action( 'bp_actions', array( $this, 'settings_action' ) );

			parent::setup_actions();
		}

		/* Navigation *********************************************************/

		/**
		 * Add the BP Block Users settings sub nav.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function setup_settings_sub_nav() {

			// Only show for those with `bp_moderate` or if you're not on your own profile.
			if ( ! bp_current_user_can( 'bp_moderate' ) || bp_is_my_profile() ) {
				return;
			}

			// Get the displayed user domain, or bail.
			if ( bp_displayed_user_domain() ) {
				$user_domain = bp_displayed_user_domain();
			} else {
				return;
			}

			// Set up the settings link.
			$slug          = bp_get_settings_slug();
			$settings_link = trailingslashit( $user_domain . $slug );

			// Set up the sub nav args array.
			$nav = array(
				'name'            => __( 'Block Member', 'bp-block-users' ),
				'slug'            => 'block-user',
				'parent_url'      => $settings_link,
				'parent_slug'     => $slug,
				'screen_function' => 'BPBU_Template_Stack::settings_screen_block_user',
				'position'        => 85,
				'user_has_access' => ! is_super_admin( bp_displayed_user_id() ),
			);

			// Add the sub nav.
			bp_core_new_subnav_item( $nav );
		}

		/**
		 * Add the `Block User` link to the WP Admin Bar.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function setup_settings_admin_bar() {

			// Bail if we're not viewing a user, or viewing self-profile.
			if ( ! bp_is_user() || bp_is_my_profile() ) {
				return;
			}

			// Bail if the user can't moderate.
			if ( ! bp_current_user_can( 'bp_moderate' ) ) {
				return;
			}

			// Bail if the settings component isn't active.
			if ( ! bp_is_active( 'settings' ) ) {
				return;
			}

			global $wp_admin_bar;

			// Set up the BP global.
			$menu_id = buddypress()->user_admin_menu_id;

			// Add our `Block User` link to the WP admin bar.
			$wp_admin_bar->add_menu( array(
				'parent' => $menu_id,
				'id'     => $menu_id . '-block-user',
				'title'  => __( 'Block User', 'bp-block-users' ),
				'href'   => trailingslashit( bp_loggedin_user_domain() . bp_get_settings_slug() ) . 'block-user/',
			) );
		}

		/* Notification Emails ************************************************/

		/**
		 * Prevent email notifications for blocked users.
		 *
		 * @since 1.0.0
		 *
		 * @param mixed  $retval   Null or new short-circuited meta value.
		 * @param int    $user_id  The user id.
		 * @param string $meta_key The meta key.
		 * @param bool   $single   Whether to return an array, or the the meta value.
		 *
		 * @return mixed `no` if blocking a user email notification.
		 */
		public function block_notifications( $retval, $user_id, $meta_key, $single ) {

			// Bail early if we have no user id or meta key.
			if ( empty( $user_id ) || empty( $meta_key ) ) {
				return $retval;
			}

			// Set up the default notification keys meta array.
			$keys = array(
				'notification_activity_new_mention',
				'notification_activity_new_reply',
				'notification_friends_friendship_request',
				'notification_friends_friendship_accepted',
				'notification_groups_invite',
				'notification_groups_group_updated',
				'notification_groups_admin_promotion',
				'notification_groups_membership_request',
				'notification_messages_new_message',
			);

			// Fire the deprecated filter.
			$keys = bpbu_apply_filters_deprecated(
				'tba_bp_block_users_block_notifications_meta_keys',
				array( $keys ),
				'1.0.0',
				'bpbu_block_notifications_meta_keys'
			);

			/**
			 * Filters the array of notification meta keys to block.
			 *
			 * @since 1.0.0
			 *
			 * @param array $keys MySQL expiration timestamp. Unix if `$int` is
			 */
			$keys = apply_filters(
				'bpbu_block_notifications_meta_keys',
				array_map( 'bp_get_user_meta_key', $keys )
			);

			// Bail if we're not checking a notification key.
			if ( ! in_array( $meta_key, $keys, true ) ) {
				return $retval;
			}

			// If the user is blocked, set to `no`.
			if ( 'no' !== $retval && BPBU_User::is_blocked( $user_id ) ) {
				$retval = 'no';
			}

			// Fire the deprecated filter.
			$retval = bpbu_apply_filters_deprecated(
				'tba_bp_block_users_block_notifications_value',
				array( $retval, $user_id, $meta_key, $single ),
				'1.0.0',
				'bpbu_block_notifications_value'
			);

			/**
			 * Filters the return of the notification meta value.
			 *
			 * @since 1.0.0
			 *
			 * @param mixed  $retval   Null or new short-circuited meta value.
			 * @param int    $user_id  The user id.
			 * @param string $meta_key The meta key.
			 * @param bool   $single   Whether to return an array, or the the meta value.
			 */
			return apply_filters( 'bpbu_block_notifications_value', $retval, $user_id, $meta_key, $single );
		}

		/* Authentication *****************************************************/

		/**
		 * Prevents the login of a blocked user.
		 *
		 * @since 1.0.0
		 *
		 * @param null|WP_User $user The WP_User object being authenticated.
		 *
		 * @return WP_User|WP_Error WP_User object if not blocked. WP_Error object,
		 *                          otherwise. Passed by reference.
		 */
		public function prevent_blocked_user_login( $user = null ) {

			// Bail early if login has already failed.
			if ( is_wp_error( $user ) || empty( $user->ID ) ) {
				return $user;
			}

			// Set the user id.
			$user_id = (int) $user->ID;

			// If the user is blocked, set the wp-login.php error message.
			if ( BPBU_User::is_blocked( $user_id ) ) {

				// Set the default message.
				$message = __( '<strong>ERROR</strong>: This account has been temporarily blocked.', 'bp-block-users' );

				// Check to see if this is a temporary block.
				$expiration = BPBU_User::get_expiration( $user_id );
				if ( '3000-01-01 00:00:00' === $expiration ) {
					$message = __( '<strong>ERROR</strong>: This account has been blocked.', 'bp-block-users' );
				}

				// Set an error object to short-circuit the authentication process.
				$user = new WP_Error( 'bpbu_authentication_blocked', $message );
			}

			// Fire the deprecated filter.
			bpbu_do_action_deprecated(
				'tba_bp_prevent_blocked_user_login',
				array( $user_id, &$user ),
				'1.0.0',
				'bpbu_prevent_blocked_user_login',
				__( 'This is now a filter, rather than an action.', 'bp-block-users' )
			);

			/**
			 * Filters the return of the authenticating user object.
			 *
			 * @since 1.0.0
			 *
			 * @param WP_User|WP_Error $user    WP_User object if not blocked. WP_Error object, otherwise.
			 * @param int              $user_id Whether this is a user update.
			 */
			return apply_filters( 'bpbu_prevent_blocked_user_login', $user, $user_id );
		}

		/* Settings Actions ***************************************************/

		/**
		 * Block/unblock a user when editing from a BP profile page.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function settings_action() {

			// Bail if no submit action.
			if ( ! isset( $_POST['block-user-submit'] ) ) {
				return;
			}

			// Bail if not in settings or the `block-user` action.
			if ( ! bp_is_settings_component() || ! bp_is_current_action( 'block-user' ) ) {
				return;
			}

			// 404 if there are any additional action variables attached.
			if ( bp_action_variables() ) {
				bp_do_404();
				return;
			}

			// If can't `bp_moderate` or on own profile, bail.
			if ( ! bp_current_user_can( 'bp_moderate' ) || bp_is_my_profile() ) {
				return;
			}

			// Nonce check.
			check_admin_referer( 'block-user' );

			bpbu_do_action_deprecated(
				'tba_bp_settings_block_user_before_save',
				array(),
				'1.0.0',
				'bpbu_settings_block_user_before_save'
			);

			/**
			 * Fires before the block user settings have been saved.
			 *
			 * @since 1.0.0
			 */
			do_action( 'bpbu_settings_block_user_before_save' );

			// Get the $_POST variables.
			$post = self::get_block_user_post_vars();

			// Block/unblock the user.
			if ( $post['block'] ) {
				BPBU_User::block( bp_displayed_user_id(), $post['length'], $post['unit'] );
			} else {
				BPBU_User::unblock( bp_displayed_user_id() );
			}

			bpbu_do_action_deprecated(
				'tba_bp_settings_block_user_after_save',
				array(),
				'1.0.0',
				'bpbu_settings_block_user_after_save'
			);

			/**
			 * Fires after the block user settings have been saved.
			 *
			 * @since 1.0.0
			 */
			do_action( 'bpbu_settings_block_user_after_save' );
		}

		/**
		 * Filters the retrieval of user meta to add a fallback for the old user
		 * meta keys.
		 *
		 * @since 1.0.0
		 *
		 * @param mixed  $retval   The check return value. Defaults to null.
		 * @param int    $user_id  The user id.
		 * @param string $meta_key The user meta key.
		 * @param bool   $single   Whether to return a single value.
		 *
		 * @return mixed False if meta key doesn't exist.
		 */
		public function filter_deprecated_meta_keys( $retval, $user_id, $meta_key, $single ) {

			// Setup an array of deprecated keys.
			$deprecated_keys = array(
				'tba_bp_user_blocked' => array(
					'version' => '1.0.0',
					'new_key' => 'bpbu_user_blocked',
				),
				'tba_bp_user_blocked_expiration' => array(
					'version' => '1.0.0',
					'new_key' => 'bpbu_user_blocked_expiration',
				),
			);

			// Bail if we're not retrieving one of our deprecated keys.
			if ( ! array_key_exists( $meta_key, $deprecated_keys ) ) {
				return $retval;
			}

			$new_key = $deprecated_keys[ $meta_key ]['new_key'];
			$version = $deprecated_keys[ $meta_key ]['version'];

			// Throw a deprecated meta key notice.
			_doing_it_wrong(
				esc_html( $meta_key ),
				sprintf(
					/* translators: 1: the meta key, 2: the version, 3: the new meta key */
					esc_html__( 'The %1$s meta key was deprecated in version %2$s. Please use %3$s instead.', 'bp-block-users' ),
					esc_html( $meta_key ),
					esc_html( $version ),
					esc_html( $new_key )
				),
				esc_html( $version )
			);

			return get_user_meta( $user_id, $new_key, $single );
		}

		/**
		 * Gets and validates our block user $_POST variables.
		 *
		 * @since 1.0.0
		 *
		 * @return array
		 */
		public static function get_block_user_post_vars() {

			// Set the default return array.
			$retval = array(
				'block'  => false,
				'length' => 0,
				'unit'   => 'indefinitely',
			);

			if ( isset( $_POST['block-user'] ) && '1' === $_POST['block-user'] ) {
				$retval['block'] = true;
			}

			if ( isset( $_POST['block-user-length'] ) && is_numeric( $_POST['block-user-length'] ) ) {
				$retval['length'] = absint( $_POST['block-user-length'] );
			}

			if ( isset( $_POST['block-user-unit'] ) ) {
				$retval['unit'] = sanitize_key( $_POST['block-user-unit'] );
			}

			return $retval;
		}
	}
} // End if().
