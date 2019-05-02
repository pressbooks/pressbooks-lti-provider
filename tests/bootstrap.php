<?php

// Override web/wp/wp-includes/pluggable.php with mock functions
function auth_redirect() {
}

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require_once( __DIR__ . '/../../pressbooks/pressbooks.php' );
	require_once( __DIR__ . '/../../pressbooks/requires.php' );
	require_once( __DIR__ . '/../../pressbooks/requires-admin.php' );
	require_once( __DIR__ . '/../pressbooks-lti-provider.php' );
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
require_once( __DIR__ . '/../../pressbooks/tests/utils-trait.php' );

if ( ! defined( 'NONCE_KEY' ) ) {
	define( 'NONCE_KEY', '40~wF,SH)lm,Zr+^[b?_M8Z.g4gk%^gnqr+ZtnT,p6_K5.NuuN 0g@Y|T9+yBI|{' );
}
