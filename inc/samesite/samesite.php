<?php

/**
 * @package Samesite
 */

/*
Plugin Name: SameSite
Plugin URI: https://wordpress.org/plugins/samesite
Description: CSRF-protection for authentication cookies. When enable, this plugin makes sure the "SameSite" flag is set in authentication cookies, which protects users from Cross-Site Request Forgery attacks.
Version: 1.4
Author: Ayesh Karunaratne
Author URI: https://ayesh.me/open-source
License: GPLv2 or later
*/

if ( ! function_exists( 'wp_set_auth_cookie' ) ) {

	/**
	 * Log in a user by setting authentication cookies.
	 *
	 * The $remember parameter increases the time that the cookie will be kept. The
	 * default the cookie is kept without remembering is two days. When $remember is
	 * set, the cookies will be kept for 14 days or two weeks.
	 *
	 * @param int $user_id User ID
	 * @param bool $remember Whether to remember the user
	 * @param mixed $secure Whether the admin cookies should only be sent over HTTPS.
	 *                         Default is_ssl().
	 * @param string $token Optional. User's session token to use for this cookie.
	 * @since 2.5.0
	 * @since 4.3.0 Added the `$token` parameter.
	 *
	 */
	function wp_set_auth_cookie( $user_id, $remember = false, $secure = '', $token = '' ) {
		if ( $remember ) {
			/**
			 * Filters the duration of the authentication cookie expiration period.
			 *
			 * @param int $length Duration of the expiration period in seconds.
			 * @param int $user_id User ID.
			 * @param bool $remember Whether to remember the user login. Default false.
			 * @since 2.8.0
			 *
			 */
			$expiration = time() + apply_filters( 'auth_cookie_expiration', 14 * DAY_IN_SECONDS, $user_id, $remember );

			/*
			 * Ensure the browser will continue to send the cookie after the expiration time is reached.
			 * Needed for the login grace period in wp_validate_auth_cookie().
			 */
			$expire = $expiration + ( 12 * HOUR_IN_SECONDS );
		} else {
			/** This filter is documented in wp-includes/pluggable.php */
			$expiration = time() + apply_filters( 'auth_cookie_expiration', 2 * DAY_IN_SECONDS, $user_id, $remember );
			$expire = 0;
		}

		if ( '' === $secure ) {
			$secure = is_ssl();
		}

		// Front-end cookie is secure when the auth cookie is secure and the site's home URL is forced HTTPS.
		$secure_logged_in_cookie = $secure && 'https' === wp_parse_url( get_option( 'home' ), PHP_URL_SCHEME );

		/**
		 * Filters whether the connection is secure.
		 *
		 * @param bool $secure Whether the connection is secure.
		 * @param int $user_id User ID.
		 * @since 3.1.0
		 *
		 */
		$secure = apply_filters( 'secure_auth_cookie', $secure, $user_id );

		/**
		 * Filters whether to use a secure cookie when logged-in.
		 *
		 * @param bool $secure_logged_in_cookie Whether to use a secure cookie when logged-in.
		 * @param int $user_id User ID.
		 * @param bool $secure Whether the connection is secure.
		 * @since 3.1.0
		 *
		 */
		$secure_logged_in_cookie = apply_filters( 'secure_logged_in_cookie', $secure_logged_in_cookie, $user_id, $secure );

		if ( $secure ) {
			$auth_cookie_name = SECURE_AUTH_COOKIE;
			$scheme = 'secure_auth';
		} else {
			$auth_cookie_name = AUTH_COOKIE;
			$scheme = 'auth';
		}

		if ( '' === $token ) {
			$manager = WP_Session_Tokens::get_instance( $user_id );
			$token = $manager->create( $expiration );
		}

		$auth_cookie = wp_generate_auth_cookie( $user_id, $expiration, $scheme, $token );
		$logged_in_cookie = wp_generate_auth_cookie( $user_id, $expiration, 'logged_in', $token );

		/**
		 * Fires immediately before the authentication cookie is set.
		 *
		 * @param string $auth_cookie Authentication cookie value.
		 * @param int $expire The time the login grace period expires as a UNIX timestamp.
		 *                            Default is 12 hours past the cookie's expiration time.
		 * @param int $expiration The time when the authentication cookie expires as a UNIX timestamp.
		 *                            Default is 14 days from now.
		 * @param int $user_id User ID.
		 * @param string $scheme Authentication scheme. Values include 'auth' or 'secure_auth'.
		 * @param string $token User's session token to use for this cookie.
		 * @since 2.5.0
		 * @since 4.9.0 The `$token` parameter was added.
		 *
		 */
		do_action( 'set_auth_cookie', $auth_cookie, $expire, $expiration, $user_id, $scheme, $token );

		/**
		 * Fires immediately before the logged-in authentication cookie is set.
		 *
		 * @param string $logged_in_cookie The logged-in cookie value.
		 * @param int $expire The time the login grace period expires as a UNIX timestamp.
		 *                                 Default is 12 hours past the cookie's expiration time.
		 * @param int $expiration The time when the logged-in authentication cookie expires as a UNIX timestamp.
		 *                                 Default is 14 days from now.
		 * @param int $user_id User ID.
		 * @param string $scheme Authentication scheme. Default 'logged_in'.
		 * @param string $token User's session token to use for this cookie.
		 * @since 2.6.0
		 * @since 4.9.0 The `$token` parameter was added.
		 *
		 */
		do_action( 'set_logged_in_cookie', $logged_in_cookie, $expire, $expiration, $user_id, 'logged_in', $token );

		/**
		 * Allows preventing auth cookies from actually being sent to the client.
		 *
		 * @param bool $send Whether to send auth cookies to the client.
		 * @since 4.7.4
		 *
		 */
		if ( ! apply_filters( 'send_auth_cookies', true ) ) {
			return;
		}

		$base_options = [
			'expires' => $expire,
			'domain' => COOKIE_DOMAIN ? COOKIE_DOMAIN : 'example.org',
			'httponly' => true,
			'samesite' => defined( 'WP_SAMESITE_COOKIE' ) ? WP_SAMESITE_COOKIE : 'Lax',
		]; // httponly is added at samesite_setcookie();

		samesite_setcookie(
			$auth_cookie_name, $auth_cookie, $base_options + [
				'secure' => $secure,
				'path' => PLUGINS_COOKIE_PATH,
			]
		);
		samesite_setcookie(
			$auth_cookie_name, $auth_cookie, $base_options + [
				'secure' => $secure,
				'path' => ADMIN_COOKIE_PATH,
			]
		);
		samesite_setcookie(
			LOGGED_IN_COOKIE, $logged_in_cookie, $base_options + [
				'secure' => $secure_logged_in_cookie,
				'path' => COOKIEPATH,
			]
		);
		if ( COOKIEPATH !== SITECOOKIEPATH ) {
			samesite_setcookie(
				LOGGED_IN_COOKIE, $logged_in_cookie, $base_options + [
					'secure' => $secure_logged_in_cookie,
					'path' => SITECOOKIEPATH,
				]
			);
		}
	}
}

/**
 *  Function to mimic setcookie() function behaviour without PHP 7.3 as
 *  as a requirement to set SameSite flag. This function does not handle exceptional
 *  cases well (to keep its functionality minimal); Do not use for any other purpose.
 * @param $name
 * @param $value
 * @param array $options
 */
function samesite_setcookie( $name, $value, array $options ) {
	/**
	 * Fix for: https://github.com/pressbooks/pressbooks/issues/1919
	 *
	 * // We need avoid multiple headers since we use redirection from WordPress for LTI Auth.
	 * $header = 'Set-Cookie:';
	 * $header .= rawurlencode($name) . '=' . rawurlencode($value) . ';';
	 * $header .= 'expires=' . \gmdate('D, d-M-Y H:i:s T', $options['expires']) . ';';
	 * $header .= 'Max-Age=' . max(0, (int) ($options['expires'] - time())) . ';';
	 * $header .= 'path=' . rawurlencode($options['path']). ';';
	 * $header .= 'domain=' . rawurlencode($options['domain']) . ';';
	 *
	 * if (!empty($options['secure'])) {
	 *    $header .= 'secure;';
	 * }
	 * $header .= 'httponly;';
	 * $header .= 'SameSite=' . rawurlencode($options['samesite']);
	 * header($header, false);
	 */

	$_COOKIE[ $name ] = $value;
	setcookie( $name, $value, $options );
}
