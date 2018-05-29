<?php

namespace Pressbooks\Lti\Provider;

/**
 * If not in unit tests, then exit!
 */
function do_exit() {
	if ( ! defined( 'WP_TESTS_MULTISITE' ) ) {
		exit;
	}
}

/**
 * Generate a globally unique identifier (GUID)
 *
 * @param $site_option bool (optional)
 *
 * @return string
 */
function globally_unique_identifier( $site_option = false ) {
	$guid = $site_option ? get_site_option( Admin::OPTION_GUID ) : get_option( Admin::OPTION_GUID );
	if ( ! $guid ) {
		if ( function_exists( 'com_create_guid' ) === true ) {
			$guid = trim( com_create_guid(), '{}' );
		} else {
			$guid = sprintf( '%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand( 0, 65535 ), mt_rand( 0, 65535 ), mt_rand( 0, 65535 ), mt_rand( 16384, 20479 ), mt_rand( 32768, 49151 ), mt_rand( 0, 65535 ), mt_rand( 0, 65535 ), mt_rand( 0, 65535 ) );
		}
		$site_option ? update_site_option( Admin::OPTION_GUID, $guid ) : update_option( Admin::OPTION_GUID, $guid );
	}
	return $guid;
}

/**
 * Processes a launch request from an LTI tool consumer
 *
 * Hooked into action: pb_do_format
 *
 * @param $format string
 */
function do_format( $format ) {
	$params = explode( '/', $format );
	$controller = array_shift( $params );
	$action = array_shift( $params );
	if ( 'lti' === $controller && \Pressbooks\Book::isBook() ) {
		$admin = Admin::init();
		$controller = new Controller( $admin );
		$controller->handleRequest( $action, $params );
		do_exit();
	}
}

/**
 * Deal with blocked 3rd Party Cookies in iframes
 */
function session_configuration() {
	if ( strpos( $_SERVER['REQUEST_URI'], '/format/lti' ) !== false ) {
		// @codingStandardsIgnoreStart
		@ini_set( 'session.use_only_cookies', false );
		@ini_set( 'session.use_trans_sid', true );
		@ini_set( 'session.use_cookies', true );
		// @codingStandardsIgnoreEnd
	}
}

/**
 * @return \Jenssegers\Blade\Blade
 */
function blade() {
	$views = __DIR__ . '/../templates';
	$cache = \Pressbooks\Utility\get_cache_path();
	$blade = new \Jenssegers\Blade\Blade( $views, $cache, new \Pressbooks\Container() );
	return $blade;
}

/**
 * Is JSON?
 *
 * @param string $string
 *
 * @return bool
 */
function is_json( $string ) {
	json_decode( $string );
	return ( json_last_error() === JSON_ERROR_NONE );
}

/**
 * @param string $suffix
 *
 * @return string
 */
function deep_link( $suffix = '' ) {
	$url = untrailingslashit( home_url() ) . '/format/lti/launch';
	if ( ! empty( $suffix ) ) {
		$url .= '/' . $suffix;
	}
	return $url;
}
