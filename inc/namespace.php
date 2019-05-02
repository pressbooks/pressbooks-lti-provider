<?php

namespace PressbooksLtiProvider;

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
	if ( 'lti' === $controller ) {
		if ( \Pressbooks\Book::isBook() ) {
			// Book
			$admin = Admin::init();
			$controller = new Controller( $admin );
			$controller->handleRequest( $action, $params );
			do_exit();
		} elseif ( ctype_digit( strval( $action ) ) && get_blog_details( $action ) !== false ) {
			$blog_id = $action;
			// Root site
			if ( isset( $params[0] ) && $params[0] === 'launch' ) {
				// PB format
				$action = array_shift( $params );
			} elseif ( isset( $_REQUEST['page_id'] ) ) {
				// Candela format
				$action = 'launch';
				$params = [ $_REQUEST['page_id'] ];
			} else {
				return; // Error: Unknown export format
			}
			switch_to_blog( $blog_id );
			$admin = Admin::init();
			$controller = new Controller( $admin );
			$controller->handleRequest( $action, $params );
			restore_current_blog();
			do_exit();
		}
	}
}

/**
 * In on the kill taker.
 */
function session_relax() {
	// By default WordPress sends an HTTP header to prevent iframe embedding on /wp_admin/ and /wp-login.php, remove them because LTI rules!
	/* @see \WP_Customize_Manager::filter_iframe_security_headers() for a better approach? */
	remove_action( 'login_init', 'send_frame_options_header' );
	remove_action( 'admin_init', 'send_frame_options_header' );
	// Keep $_SESSION alive, LTI puts info in it
	remove_action( 'wp_login', '\Pressbooks\session_kill' );
	remove_action( 'wp_logout', '\Pressbooks\session_kill' );
}

/**
 * Deal with blocked 3rd Party Cookies in iframes
 *
 * @param array $options
 *
 * @return array
 */
function session_configuration( $options = [] ) {
	if ( strpos( $_SERVER['REQUEST_URI'], '/format/lti' ) !== false ) {
		// @codingStandardsIgnoreStart
		@ini_set( 'session.use_only_cookies', false );
		@ini_set( 'session.use_trans_sid', true );
		@ini_set( 'session.use_cookies', true );
		// @codingStandardsIgnoreEnd
		if ( isset( $options['read_and_close'] ) ) {
			unset( $options['read_and_close'] );
		}
	}
	return $options;
}

/**
 * @param \WP_Error $errors
 * @param string $redirect_to Redirect destination URL.
 *
 * @return \WP_Error
 */
function login_errors( $errors, $redirect_to ) {
	if ( isset( $_SESSION['pb_lti_prompt_for_authentication'] ) ) {
		/** @var \PressbooksLtiProvider\Entities\Storage $storage */
		$storage = $_SESSION['pb_lti_prompt_for_authentication'];
		$blogname = get_blog_option( 1, 'blogname' );
		/* translators: 1: Network Name, 2: LMS Name */
		$message = sprintf( __( 'It looks like you already have an account on %1$s. Please log in to connect your Pressbooks account to your %2$s ID.' ), $blogname, $storage->lmsName );
		$errors->add( 'lti', $message );
	}
	return $errors;
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
