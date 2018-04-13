<?php

namespace Pressbooks\Lti\Provider;

/**
 * Generate a globally unique identifier (GUID)
 *
 * @return string
 */
function globally_unique_identifier() {
	$option = 'pressbooks_lti_GUID';
	$guid = get_site_option( $option );
	if ( ! $guid ) {
		if ( function_exists( 'com_create_guid' ) === true ) {
			$guid = trim( com_create_guid(), '{}' );
		} else {
			$guid = sprintf( '%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand( 0, 65535 ), mt_rand( 0, 65535 ), mt_rand( 0, 65535 ), mt_rand( 16384, 20479 ), mt_rand( 32768, 49151 ), mt_rand( 0, 65535 ), mt_rand( 0, 65535 ), mt_rand( 0, 65535 ) );
		}
		update_site_option( $option, $guid );
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
	if ( 'lti' === $controller ) {
		$controller = new Controller();
		$controller->handleRequest( $action, $params );
		exit;
	}
}

/**
 * @return \Jenssegers\Blade\Blade
 */
function blade() {
	static $blade;
	if ( empty( $blade ) ) {
		$views = __DIR__ . '/../templates';
		$cache = \Pressbooks\Utility\get_cache_path();
		$blade = new \Jenssegers\Blade\Blade( $views, $cache );
	}
	return $blade;
}
