<?php
/**
 * BP Block Users Admin.
 *
 * @package BP_Block_Users
 * @subpackage Administration
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Load BP Block Users admin area.
 *
 * @since 1.0.0
 */
class BPBU_Admin_List_Tables extends BPBU_Admin {

	/**
	 * The BP Block Users Admin List Tables instance.
	 *
	 * @since 1.0.0
	 *
	 * @var BPBU_Admin_List_Tables
	 */
	private static $instance;

	/**
	 * The BP Block Users list table.
	 *
	 * @since 1.0.0
	 *
	 * @var WP_Users_List_Table
	 */
	public static $list_table;

	/**
	 * The blocked user ids array.
	 *
	 * @since 1.0.0
	 *
	 * @var null|array
	 */
	public static $blocked_user_ids;

	/**
	 * Provides access to a single instance of `BPBU_Admin_List_Tables` using the
	 * singleton pattern.
	 *
	 * @since 1.0.0
	 *
	 * @return BPBU_Admin_List_Tables
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Constructor method.
	 *
	 * @since 1.0.0
	 */
	protected function __construct() {
		$this->setup_actions();
	}

	/**
	 * Set admin-related actions and filters.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function setup_actions() {

		// Add menu item to all users menu.
		add_action( 'admin_menu',         array( $this, 'admin_menu' ), 5 );
		add_action( 'network_admin_menu', array( $this, 'admin_menu' ), 5 );

		// Add a row actions to users listing.
		if ( bp_core_do_network_admin() ) {
			add_filter( 'ms_user_row_actions', array( $this, 'row_actions' ), 10, 2 );
		}

		// Add user row actions for single site.
		add_filter( 'user_row_actions', array( $this, 'row_actions' ), 10, 2 );

		// Add the blocked users view.
		if ( current_user_can( $this->capability ) ) {
			$screen = bp_core_do_network_admin() ? 'users-network' : 'users';
			add_filter( "views_{$screen}", array( $this, 'filter_view' ) );
			add_filter( 'set-screen-option', array( $this, 'screen_options' ), 10, 3 );
		}
	}

	/**
	 * Create the All Users > Manage Blocked Users submenu.
	 *
	 * @since 1.0.0
	 */
	public function admin_menu() {

		// Only show blocked users where they belong.
		if ( ! is_multisite() || is_network_admin() ) {

			// Manage blocked users.
			$hook = add_users_page(
				__( 'Manage Blocked Users', 'bp-block-users' ),
				__( 'Manage Blocked Users', 'bp-block-users' ),
				$this->capability,
				'bp-block-users',
				array( $this, 'admin_index' )
			);
		}

		// Append '-network' to each array item if in network admin.
		if ( is_network_admin() ) {
			$hook .= '-network';
		}

		// Add the block users admin loader.
		add_action( "load-{$hook}", array( $this, 'admin_load' ) );
	}

	/**
	 * Add a `Block/Unblock` link to the user row action links.
	 *
	 * @since 1.0.0
	 *
	 * @param array        $actions An array of row actions.
	 * @param null|WP_User $user    The WP_User object.
	 *
	 * @return array An array of row actions.
	 */
	public function row_actions( $actions = array(), $user = null ) {

		// Validate the user_id.
		if ( empty( $user->ID ) ) {
			return $actions;
		}

		// Bail if user can't block.
		if ( ! $this->current_user_can_block( $user->ID ) ) {
			return $actions;
		}

		// Setup args array.
		$args = array();

		// Add the user ID.
		$args['user_id'] = $user->ID;

		// Add a referer.
		$args['wp_http_referer'] = rawurlencode( wp_unslash( $_SERVER['REQUEST_URI'] ) );

		$is_blocked = BPBU_User::is_blocked( $user->ID );

		if ( $is_blocked ) {
			$args['action'] = 'unblock';
			$args['page']   = 'bp-block-users';
			$text = __( 'Unblock', 'bp-block-users' );
			$url = bp_get_admin_url( 'users.php' );
			$url = add_query_arg( $args, $url );
			$url = wp_nonce_url( $url, 'unblock_single' );
		} else {
			$text = __( 'Block', 'bp-block-users' );
			$url = get_edit_user_link( $user->ID ) . '#block-user';
			$url = add_query_arg( $args, $url );
		}

		// Setup the Block/Unblock link.
		$link = sprintf( '<a href="%1$s">%2$s</a>', esc_url( $url ), esc_html( $text ) );

		// Add the block link to the actions array.
		$actions['block'] = $link;

		return $actions;
	}

	/**
	 * Filter the WP Users List Table views to include blocked users.
	 *
	 * @since 1.0.0
	 *
	 * @param array $views WP List Table views.
	 *
	 * @return array The views with the blocked view added.
	 */
	public function filter_view( $views = array() ) {
		global $role;

		// Remove the 'current' class from All if we're on the blocked view.
		if ( 'blocked' === $role ) {
			$views['all'] = str_replace( 'class="current"', '', $views['all'] );
			$class        = 'current';
		} else {
			$class        = '';
		}

		// Get the appropriate admin url.
		if ( is_network_admin() ) {
			$base_url = network_admin_url( 'users.php' );
		} else {
			$base_url = bp_get_admin_url( 'users.php' );
		}

		if ( ! isset( self::$blocked_user_ids ) ) {
			self::$blocked_user_ids = BPBU_User::get_blocked_user_ids();
		}

		// Set up the blocked users view variables.
		$count = count( self::$blocked_user_ids );
		$url   = add_query_arg( 'page', 'bp-block-users', $base_url );
		/* translators: 1: Blocked user count */
		$text  = sprintf( _x( 'Blocked %s', 'blocked users', 'bp-block-users' ), '<span class="count">(' . number_format_i18n( $count ) . ')</span>' );

		$views['blocked'] = sprintf( '<a href="%1$s" class="%2$s">%3$s</a>', esc_url( $url ), $class, $text );

		return $views;
	}

	/**
	 * Display the admin preferences about blocked users pagination.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $value     The current pagination value.
	 * @param string $option    The pagination option.
	 * @param int    $new_value The new pagination value.
	 *
	 * @return int The pagination preferences.
	 */
	public function screen_options( $value = 0, $option = '', $new_value = 0 ) {
		if ( 'users_page_bpbu_network_per_page' !== $option && 'users_page_bpbu_per_page' !== $option ) {
			return $value;
		}

		// Per page.
		$new_value = (int) $new_value;
		if ( $new_value < 1 || $new_value > 999 ) {
			return $value;
		}

		return $new_value;
	}

	/**
	 * Load the BP Block Users List table.
	 *
	 * @since 1.0.0
	 *
	 * @return WP_Users_List_Table The list table.
	 */
	public static function get_list_table() {

		if ( ! isset( self::$blocked_user_ids ) ) {
			self::$blocked_user_ids = BPBU_User::get_blocked_user_ids();
		}

		if ( bp_core_do_network_admin() ) {
			$table_prefix = 'ms-users';
			$list_table   = 'BPBU_MS_Users_List_Table';
		} else {
			$table_prefix = 'users';
			$list_table   = 'BPBU_Users_List_Table';
		}

		if ( null === self::$list_table ) {
			require_once( ABSPATH . "wp-admin/includes/class-wp-{$table_prefix}-list-table.php" );
			require_once( buddypress()->block_users->classes_dir . "class-bpbu-{$table_prefix}-list-table.php" );

			self::$list_table = new $list_table();
		}

		return self::$list_table;
	}

	/**
	 * Set up the admin help.
	 *
	 * @since 1.0.0
	 */
	private function admin_help() {
		// The per_page screen option.
		add_screen_option(
			'per_page',
			array(
				'label' => _x( 'Blocked Users', 'Blocked Users per page (screen options)', 'bp-block-users' ),
			)
		);

		get_current_screen()->add_help_tab( array(
			'id'      => 'bp-block-users-overview',
			'title'   => __( 'Overview', 'bp-block-users' ),
			'content' =>
				'<p>'
				. __( 'This is the administration screen for blocked users on your site.', 'bp-block-users' )
				. '</p><p>'
				. __( 'From the screen options, you can customize the displayed columns and the pagination of this screen.', 'bp-block-users' )
				. '</p><p>'
				. __( 'You can reorder the list of your blocked users by clicking on the Username or Email column headers.', 'bp-block-users' )
				. '</p><p>'
				. __( 'Using the search form, you can find blocked users more easily. The Username and Email fields will be included in the search.', 'bp-block-users' )
				. '</p>',
		) );

		get_current_screen()->add_help_tab( array(
			'id'      => 'bp-block-users-actions',
			'title'   => __( 'Actions', 'bp-block-users' ),
			'content' =>
				'<p>'
				. __( 'Hovering over a row in the blocked users list will display action links that allow you to manage blocked users. You can perform the following actions:', 'bp-block-users' )
				. '</p><ul><li>'
				. __( '"Edit Expiration" takes you to the confirmation screen before being able to send the activation link to the desired pending account. You can only send the activation email once per day.', 'bp-block-users' )
				. '</li><li>'
				. __( '"Unblock" allows you to delete a pending account from your site. You will be asked to confirm this deletion.', 'bp-block-users' )
				. '</li></ul><p>'
				. __( 'By clicking on a Username you will be able to activate a pending account from the confirmation screen.', 'bp-block-users' )
				. '</p><p>'
				. __( 'Bulk actions allow you to perform these 3 actions for the selected rows.', 'bp-block-users' )
				. '</p>',
		) );

		// Help panel - sidebar links.
		get_current_screen()->set_help_sidebar(
			'<p><strong>'
			. __( 'For more information:', 'bp-block-users' )
			. '</strong></p><p>'
			. __( '<a href="https://wordpress.org/support/plugin/bp-block-users/">Support Forums</a>', 'bp-block-users' )
			. '</p>'
		);
	}

	/**
	 * Set up the blocked users admin page.
	 *
	 * Loaded before the page is rendered, this function does all initial
	 * setup, including: processing form requests, registering contextual
	 * help, and setting up screen options.
	 *
	 * @since 1.0.0
	 */
	public function admin_load() {

		// Get the current action.
		$doaction = bp_admin_list_table_current_bulk_action();

		/**
		 * Fires at the start of the blocked users admin load.
		 *
		 * @since 1.0.0
		 *
		 * @param string $doaction Current bulk action being processed.
		 */
		do_action( 'bpbu_admin_load', $doaction );

		// Process any requested unblock actions.
		$this->unblock_handler( $doaction );

		// Load the list table class.
		self::get_list_table();

		// Load the admin help.
		$this->admin_help();

	}

	/**
	 * This is the list of the Blocked users.
	 *
	 * @since 1.0.0
	 *
	 * @global $plugin_page
	 */
	public function admin_index() {
		global $usersearch, $plugin_page;

		// Prepare the group items for display.
		self::$list_table->prepare_items();

		$form_url = add_query_arg(
			array(
				'page' => 'bp-block-users',
			),
			bp_get_admin_url( 'users.php' )
		);

		$search_form_url = $this->remove_query_args( $_SERVER['REQUEST_URI'] );

		?>

		<div class="wrap">
			<h1><?php esc_html_e( 'Users', 'bp-block-users' ); ?>

				<?php if ( current_user_can( 'create_users' ) ) : ?>

					<a href="user-new.php" class="add-new-h2"><?php echo esc_html_x( 'Add New', 'user', 'bp-block-users' ); ?></a>

				<?php elseif ( is_multisite() && current_user_can( 'promote_users' ) ) : ?>

					<a href="user-new.php" class="add-new-h2"><?php echo esc_html_x( 'Add Existing', 'user', 'bp-block-users' ); ?></a>

				<?php endif; ?>

				<?php if ( $usersearch ) : ?>

					<span class="subtitle"><?php
						/* translators: The search query string */
						sprintf( __( 'Search results for &#8220;%s&#8221;', 'bp-block-users' ), esc_html( $usersearch ) );
					?></span>

				<?php endif; ?>

			</h1>

			<?php self::$list_table->views(); ?>

			<form id="bp-block-users-search-form" action="<?php echo esc_url( $search_form_url ); ?>">
				<input type="hidden" name="page" value="<?php echo esc_attr( $plugin_page ); ?>" />
				<?php self::$list_table->search_box( __( 'Search Blocked Users', 'bp-block-users' ), 'bp-block-users' ); ?>
			</form>

			<form id="bp-block-users-form" action="<?php echo esc_url( $form_url );?>" method="post">
				<?php self::$list_table->display(); ?>
			</form>
		</div>
	<?php
	}

	/**
	 * Handles the bulk or single unblocking.
	 *
	 * @since 1.0.0
	 *
	 * @param string $doaction The current action.
	 *
	 * @return void
	 */
	private function unblock_handler( $doaction = '' ) {

		// Bail if we're not unblocking.
		if ( 'unblock' !== $doaction ) {
			return;
		}

		// Build redirection URL.
		$redirect_url = $_SERVER['REQUEST_URI'];
		if ( ! empty( $_GET['wp_http_referer'] ) ) {
			$redirect_url = $_GET['wp_http_referer'];
		}

		$redirect_to = $this->remove_query_args( $redirect_url );

		// Get the user IDs from the URL.
		$ids = array();
		if ( ! empty( $_POST['allblockedusers'] ) ) {
			$ids   = wp_parse_id_list( $_POST['allblockedusers'] );
			$nonce = 'bulk-users';
		} elseif ( ! empty( $_GET['user_id'] ) ) {
			$ids   = (array) absint( $_GET['user_id'] );
			$nonce = 'unblock_single';
		}

		// Nonce check.
		check_admin_referer( $nonce );

		$query_args = array(
			'updated'      => 'unblocked',
			'unblocked'    => '',
			'notunblocked' => '',
		);

		// Loop through the user ids and unblock.
		foreach ( $ids as $user_id ) {

			// Default to a failed unblock.
			$unblocked = false;

			// Unblock only if allowed.
			if ( $this->current_user_can_block( $user_id ) ) {
				$unblocked = BPBU_User::unblock( $user_id );
			}

			// Set the array key.
			$key = $unblocked ? 'unblocked' : 'notunblocked';

			// Add the user id to the unblocked/errors array.
			$query_args[ $key ] .= $user_id . ',';
		}

		$query_args['unblocked']    = trim( $query_args['unblocked'], ',' );
		$query_args['notunblocked'] = trim( $query_args['notunblocked'], ',' );

		// Set the redirect url.
		$redirect_to = add_query_arg( $query_args, $redirect_to );

		bp_core_redirect( $redirect_to );
	}
}
