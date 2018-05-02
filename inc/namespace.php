<?php

namespace Pressbooks\Lti\Provider;

/**
 * Generate a globally unique identifier (GUID)
 *
 * @return string
 */
function globally_unique_identifier() {
	$guid = get_site_option( Admin::OPTIION_GUID );
	if ( ! $guid ) {
		if ( function_exists( 'com_create_guid' ) === true ) {
			$guid = trim( com_create_guid(), '{}' );
		} else {
			$guid = sprintf( '%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand( 0, 65535 ), mt_rand( 0, 65535 ), mt_rand( 0, 65535 ), mt_rand( 16384, 20479 ), mt_rand( 32768, 49151 ), mt_rand( 0, 65535 ), mt_rand( 0, 65535 ), mt_rand( 0, 65535 ) );
		}
		update_site_option( Admin::OPTIION_GUID, $guid );
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
		$controller = new Controller();
		$controller->handleRequest( $action, $params );
		exit;
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
 * Check if a domain is whitelisted
 *
 * @param $domain
 *
 * @return bool
 */
function is_whitelisted( $domain ) {

	$whitelist = get_site_option( Admin::OPTION_WHITELIST, '' );
	if ( ! is_array( $whitelist ) ) {
		$whitelist = explode( "\n", $whitelist );
	}

	// Remove empty entries
	$whitelist = array_filter(
		$whitelist,
		function ( $var ) {
			if ( is_string( $var ) ) {
				$var = trim( $var );
			}
			return ! empty( $var );
		}
	);
	if ( empty( $whitelist ) ) {
		return true;
	}

	$whitelist = array_map( 'strtolower', $whitelist );
	$domain = strtolower( $domain );
	foreach ( $whitelist as $allowed ) {
		if ( $domain === $allowed ) {
			return true;
		}
		$dotted_domain = ".$allowed";
		if ( $dotted_domain === substr( $domain, -strlen( $dotted_domain ) ) ) {
			return true;
		}
	}

	return false;
}
