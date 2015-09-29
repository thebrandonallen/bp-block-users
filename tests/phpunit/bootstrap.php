<?php

// Define our constants
echo "Defining constants...\n";
require( dirname( __FILE__ ) . '/includes/define-constants.php' );

echo "Ensure BP Block Users is an active plugin...\n";
$GLOBALS['wp_tests_options'] = array(
	'active_plugins' => array( 'bp-block-users/bp-block-users.php' ),
);

// Bail if test suite cannot be found
if ( ! file_exists( WP_TESTS_DIR . '/includes/functions.php' ) ) {
	die( "The WordPress PHPUnit test suite could not be found.\n" );
} else {
	echo "Loading WordPress PHPUnit test suite...\n";
	require( WP_TESTS_DIR . '/includes/functions.php' );
}

/**
 * Load BP Block Users' PHPUnit test suite loader
 */
function _load_loader() {

	// If BuddyPress is found, set it up and require it.
	if ( defined( 'BP_TESTS_DIR' ) ) {
		require BP_TESTS_DIR . '/includes/loader.php';
	}

	require( BPBU_TESTS_DIR . '/includes/loader.php' );
}
tests_add_filter( 'muplugins_loaded', '_load_loader' );

echo "Loading WordPress bootstrap...\n";
require( WP_TESTS_DIR . '/includes/bootstrap.php' );

echo "Loading BP Block Users testcase...\n";
require( BPBU_TESTS_DIR . '/includes/testcase.php' );
//require( BPBU_TESTS_DIR . '/includes/factory.php' );

echo "Loading BuddyPress testcase...\n";
require BP_TESTS_DIR . '/includes/testcase.php';
