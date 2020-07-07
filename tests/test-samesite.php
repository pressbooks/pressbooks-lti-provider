<?php

/**
 * Class SamesiteTest
 * @group samesite
 */
class SamesiteTest extends \WP_UnitTestCase {


	public function setUp() {
		parent::setUp();
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_setCookie() {
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		wp_clear_auth_cookie();
		add_filter( 'send_auth_cookies', '__return_true' );
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id, true );
		$cookie_session = wp_parse_auth_cookie();
		// PHP and WP don't store SameSite parameter: https://stackoverflow.com/questions/3174128/getting-a-cookies-parameters-in-php
		// Check if session was stored
		$this->assertArrayHasKey( 'token', $cookie_session );
		$this->assertArrayHasKey( 'scheme', $cookie_session );
		$this->assertEquals( $cookie_session['scheme'], 'auth' );
	}
}


