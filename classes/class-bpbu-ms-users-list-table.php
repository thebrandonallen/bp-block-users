<?php
/**
 * BP Block Users MS List Table
 *
 * @package BP_Block_Users
 * @subpackage List_Tables
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WP_MS_Users_List_Table' ) ) {

	/**
	 * List table class for blocked users admin page.
	 *
	 * @since 1.0.0
	 */
	class BPBU_MS_Users_List_Table extends WP_MS_Users_List_Table {

		/**
		 * Blocked user counts.
		 *
		 * @since 1.0.0
		 *
		 * @access public
		 * @var int
		 */
		public $user_count = 0;

		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			// Define singular and plural labels, as well as whether we support AJAX.
			parent::__construct( array(
				'ajax'     => false,
				'plural'   => 'blocked-users',
				'singular' => 'blocked-user',
				'screen'   => get_current_screen()->id,
			) );
		}

		/**
		 * Set up items for display in the list table.
		 *
		 * Handles filtering of data, sorting, pagination, and any other data
		 * manipulation required prior to rendering.
		 *
		 * @since 1.0.0
		 */
		public function prepare_items() {
			global $usersearch, $role, $mode;

			// Set up our arguments.
			$order      = isset( $_REQUEST['order'] ) ? sanitize_key( $_REQUEST['order'] ) : 'DESC';
			$orderby    = isset( $_REQUEST['orderby'] ) ? sanitize_key( $_REQUEST['orderby'] ) : 'ID';
			$paged      = $this->get_pagenum();
			$per_page   = $this->get_items_per_page( str_replace( '-', '_', "{$this->screen->id}_per_page" ) );
			$usersearch = isset( $_REQUEST['s'] ) ? wp_unslash( trim( $_REQUEST['s'] ) ) : '';

			$blocked_user_ids = BPBU_Admin_List_Tables::$blocked_user_ids;
			$args             = array();

			if ( ! empty( $blocked_user_ids ) ) {
				$args = array(
					'offset'  => ( $paged - 1 ) * $per_page,
					'number'  => $per_page,
					'search'  => '',
					'orderby' => $orderby,
					'order'   => $order,
					'include' => $blocked_user_ids,
				);

				// Set up a wildcard search.
				if ( '' !== $usersearch ) {
					$args['search'] = '*' . $usersearch . '*';
				}
			}

			// Set the globals.
			$role = isset( $_REQUEST['role'] ) ? $_REQUEST['role'] : '';
			$mode = empty( $_REQUEST['mode'] ) ? 'list' : $_REQUEST['mode'];

			// Set up our user variables.
			$users_query      = new WP_User_Query( $args );
			$this->items      = $users_query->results;
			$this->user_count = $users_query->total_users;

			// Set our pagination arguments.
			$this->set_pagination_args( array(
				'total_items' => $this->user_count,
				'per_page'    => $per_page,
			) );
		}

		/**
		 * The text shown when no items are found.
		 *
		 * Nice job, clean sheet!
		 *
		 * @since 1.0.0
		 */
		public function no_items() {
			esc_html_e( 'No blocked users found.', 'bp-block-users' );
		}

		/**
		 * Display the users screen views.
		 *
		 * @since 1.0.0
		 *
		 * @global string $role The name of role the users screens is filtered by.
		 */
		public function views() {
			global $role;

			// Used to reset the role.
			$reset_role = $role;

			// Temporarly set the role to registered.
			$role = 'blocked';

			// Used to reset the screen id once views are displayed.
			$reset_screen_id = $this->screen->id;

			// Temporarly set the screen id to the users one.
			$this->screen->id = 'users-network';

			// Use the parent function so that other plugins can safely add views.
			parent::views();

			// Reset the role.
			$role = $reset_role;

			// Reset the screen id.
			$this->screen->id = $reset_screen_id;
		}

		/**
		 * Specific bulk actions for blocked users.
		 *
		 * @since 1.0.0
		 */
		protected function get_bulk_actions() {

			$actions = array();

			if ( current_user_can( 'edit_users' ) ) {
				$actions['unblock'] = _x( 'Unblock', 'Unblock users action', 'bp-block-users' );
			}

			return $actions;
		}

		/**
		 * Specific blocked users columns.
		 *
		 * @since 1.0.0
		 */
		public function get_columns() {

			/**
			 * Filters the multisite blocked users columns.
			 *
			 * @since 1.0.0
			 *
			 * @param array $columns Array of columns to display.
			 */
			return apply_filters( 'bpbu_ms_blocked_users_columns', array(
				'cb'         => '<input type="checkbox" />',
				'username'   => __( 'Username',   'bp-block-users' ),
				'name'       => __( 'Name',       'bp-block-users' ),
				'email'      => __( 'Email',      'bp-block-users' ),
				'expiration' => __( 'Expiration', 'bp-block-users' ),
			) );
		}

		/**
		 * Display blocked users rows.
		 *
		 * @since 1.0.0
		 */
		public function display_rows() {

			$style = '';
			foreach ( $this->items as $user ) {

				$style = ( ' class="alternate"' === $style ) ? '' : ' class="alternate"';
				echo "\n\t" . $this->single_row( $user, $style );
			}
		}

		/**
		 * Display a blocked user row.
		 *
		 * @since 1.0.0
		 *
		 * @see WP_List_Table::single_row() for explanation of params.
		 *
		 * @param WP_User|null $user     The blocked user data object.
		 * @param string       $style    Styles for the row.
		 * @param string       $role     Role to be assigned to user.
		 * @param int          $numposts Numper of posts.
		 */
		public function single_row( $user = null, $style = '', $role = '', $numposts = 0 ) {
			echo '<tr' . $style . ' id="blocked-user-' . esc_attr( $user->ID ) . '">';
			echo $this->single_row_columns( $user );
			echo '</tr>';
		}

		/**
		 * Markup for the checkbox used to select items for bulk actions.
		 *
		 * @since 1.0.0
		 *
		 * @param WP_User $user The blocked user data object.
		 */
		protected function column_cb( $user = null ) {
		?>
			<label class="screen-reader-text" for="blocked-user-<?php echo intval( $user->ID ); ?>"><?php
				/* translators: User login name */
				printf( esc_html__( 'Select user: %s', 'bp-block-users' ), esc_html( $user->user_login ) );
			?></label>
			<input type="checkbox" id="blocked-user-<?php echo intval( $user->ID ) ?>" name="allblockedusers[]" value="<?php echo esc_attr( $user->ID ) ?>" />
			<?php
		}

		/**
		 * The row actions (delete/activate/email).
		 *
		 * @since 1.0.0
		 *
		 * @param WP_User $user The blocked user data object.
		 */
		protected function column_username( $user = null ) {
			$avatar	= get_avatar( $user->user_email, 32 );

			// Edit user link.
			$edit_link = get_edit_user_link( $user->ID ) . '#block-user';

			echo $avatar . sprintf( '<strong><a href="%1$s" class="edit" title="%2$s">%3$s</a></strong><br/>', esc_url( $edit_link ), esc_attr__( 'Edit Expiration', 'bp-block-users' ), esc_html( $user->user_login ) );

			$actions = array();

			$actions['edit'] = sprintf( '<a href="%1$s">%2$s</a>', esc_url( $edit_link ), __( 'Edit Expiration', 'bp-block-users' ) );

			// Unblock user link.
			$unblock_link = add_query_arg(
				array(
					'page'	  => 'bp-block-users',
					'user_id' => $user->ID,
					'action'  => 'unblock',
				),
				bp_get_admin_url( 'users.php' )
			);
			$unblock_link = wp_nonce_url( $unblock_link, 'unblock_single' );

			$actions['unblock'] = sprintf( '<a href="%1$s">%2$s</a>', esc_url( $unblock_link ), esc_html__( 'Unblock', 'bp-block-users' ) );

			/**
			 * Filters the multisite row actions for each user in list.
			 *
			 * @since 1.0.0
			 *
			 * @param array   $actions Array of actions and corresponding links.
			 * @param WP_User $user    The blocked user data object.
			 */
			$actions = apply_filters( 'bpbu_ms_users_row_actions', $actions, $user );

			echo $this->row_actions( $actions );
		}

		/**
		 * Display user name, if any.
		 *
		 * @since 1.0.0
		 *
		 * @param WP_User $user The blocked user data object.
		 */
		protected function column_name( $user = null ) {
			echo esc_html( $user->display_name );
		}

		/**
		 * Display user email.
		 *
		 * @since 1.0.0
		 *
		 * @param WP_User $user The blocked user data object.
		 */
		protected function column_email( $user = null ) {
			printf( '<a href="mailto:%1$s">%2$s</a>', esc_attr( $user->user_email ), esc_html( $user->user_email ) );
		}

		/**
		 * Display the block expiration time of user.
		 *
		 * @since 1.0.0
		 *
		 * @param WP_User $user The blocked user data object.
		 *
		 * @return void
		 */
		protected function column_expiration( $user = null ) {

			if ( ! BPBU_User::is_blocked( $user->ID ) ) {
				echo esc_html( __( 'Not Blocked', 'bp-block-users' ) );
				return;
			}

			$expiration = BPBU_User::get_expiration( $user->ID );
			$expiration_int = strtotime( $expiration );

			// If the expiration is not a timestamp, the user is blocked indefinitely.
			if ( '3000-01-01 00:00:00' === $expiration ) {
				$expiration = __( 'Never', 'bp-block-users' );

			// Display when the user's block will expire.
			} elseif ( $expiration_int > time() ) {

				// Set the date and time of the block expiration.
				$date = date_i18n( bp_get_option( 'date_format' ), $expiration_int );
				$time = get_date_from_gmt( $expiration, bp_get_option( 'time_format' ) );

				// Set the message with expiration time.
				$expiration = $date . ' ' . $time;
			}

			echo esc_html( $expiration );
		}

		/**
		 * Allow plugins to add their custom column.
		 *
		 * @since 1.0.0
		 *
		 * @param WP_User|null $user        The blocked user data object.
		 * @param string       $column_name The column name.
		 *
		 * @return string
		 */
		protected function column_default( $user = null, $column_name = '' ) {

			/**
			 * Filters the single site custom columns for plugins.
			 *
			 * @since 1.0.0
			 *
			 * @param string  $column_name The column name.
			 * @param WP_User $user        The blocked user data object.
			 */
			return apply_filters( 'bpbu_ms_users_custom_column', '', $column_name, $user );
		}

		/**
		 * Prevents regular users row actions to be output.
		 *
		 * @since 1.0.0
		 * @access protected
		 *
		 * @param WP_User $user        The blocked user data object.
		 * @param string  $column_name Current column name.
		 * @param string  $primary     Primary column name.
		 *
		 * @return string
		 */
		protected function handle_row_actions( $user = null, $column_name = '', $primary = '' ) {
			return '';
		}
	}
} // End if().
