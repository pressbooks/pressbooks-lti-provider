<?php

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

if ( ! function_exists( '\HM\Autoloader\register_class_path' ) ) {
	require_once( __DIR__ . '/../../pressbooks/hm-autoloader.php' );;
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require_once( __DIR__ . '/../../pressbooks/hm-autoloader.php' );
	require_once( __DIR__ . '/../../pressbooks/pressbooks.php' );
	require_once( __DIR__ . '/../../pressbooks/requires.php' );
	require_once( __DIR__ . '/../../pressbooks/requires-admin.php' );
	require_once( __DIR__ . '/../pressbooks-lti-provider.php' );
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
