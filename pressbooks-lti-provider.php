<?php
/*
Plugin Name: Pressbooks LTI Provider
Plugin URI: https://pressbooks.org
Description: A plugin which turns Pressbooks into an LTI provider.
Version: 1.3.0-dev
Author: Pressbooks (Book Oven Inc.)
Author URI: https://pressbooks.org
Requires PHP: 7.1
Pressbooks tested up to: 5.7.0
Text Domain: pressbooks-lti-provider
License: GPLv3 or later
Network: True
*/

// -------------------------------------------------------------------------------------------------------------------
// Check requirements
// -------------------------------------------------------------------------------------------------------------------

if ( ! function_exists( 'pb_meets_minimum_requirements' ) && ! @include_once( WP_PLUGIN_DIR . '/pressbooks/compatibility.php' ) ) { // @codingStandardsIgnoreLine
	add_action(
		'admin_notices', function () {
			echo '<div id="message" role="alert" class="error fade"><p>' . __( 'Cannot find Pressbooks install.', 'pressbooks-lti-provider' ) . '</p></div>';
		}
	);
	return;
} elseif ( ! pb_meets_minimum_requirements() ) {
	return;
}

if ( ! class_exists( '\PDO' ) || ! in_array( 'mysql', \PDO::getAvailableDrivers(), true ) ) {
	add_action(
		'admin_notices', function () {
			echo '<div id="message" role="alert" class="error fade"><p>' . __( 'Cannot find PDO for MySQL.', 'pressbooks-lti-provider' ) . '</p></div>';
		}
	);
	return;
}


// -------------------------------------------------------------------------------------------------------------------
// Class autoloader
// -------------------------------------------------------------------------------------------------------------------

\HM\Autoloader\register_class_path( 'PressbooksLtiProvider', __DIR__ . '/inc' );

// -------------------------------------------------------------------------------------------------------------------
// Composer autoloader
// -------------------------------------------------------------------------------------------------------------------

if ( ! class_exists( '\IMSGlobal\LTI\ToolProvider\ToolProvider' ) ) {
	if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
		require_once __DIR__ . '/vendor/autoload.php';
	} else {
		$title = __( 'Dependencies Missing', 'pressbooks-lti-provider' );
		$body = __( 'Please run <code>composer install</code> from the root of the Pressbooks LTI Provider plugin directory.', 'pressbooks-lti-provider' );
		$message = "<h1>{$title}</h1><p>{$body}</p>";
		wp_die( $message, $title );
	}
}

// -------------------------------------------------------------------------------------------------------------------
// Requires
// -------------------------------------------------------------------------------------------------------------------

require( __DIR__ . '/inc/namespace.php' );

// -------------------------------------------------------------------------------------------------------------------
// Hooks
// -------------------------------------------------------------------------------------------------------------------

register_activation_hook( __FILE__, [ '\PressbooksLtiProvider\Database', 'installTables' ] );
add_action( 'plugins_loaded', [ '\PressbooksLtiProvider\Updates', 'init' ] );
add_action( 'plugins_loaded', [ '\PressbooksLtiProvider\Admin', 'init' ] );
add_action( 'pb_do_format', '\PressbooksLtiProvider\do_format' );
add_action( 'wp_loaded', '\PressbooksLtiProvider\session_relax' );
add_filter( 'pb_session_configuration', '\PressbooksLtiProvider\session_configuration' );
add_filter( 'wp_login_errors', '\PressbooksLtiProvider\login_errors', 10, 2 );
