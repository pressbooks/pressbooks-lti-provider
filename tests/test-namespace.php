<?php

class NamespaceTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * Test PB style class initializations
	 */
	public function test_classInitConventions() {
		$this->_book();
		global $wp_filter;
		$classes = [
			'\PressbooksLtiProvider\Admin',
		];
		foreach ( $classes as $class ) {
			$result = $class::init();
			$this->assertInstanceOf( $class, $result );
			$class::hooks( $result );
			$this->assertNotEmpty( $wp_filter );
		}
	}

	public function test_globally_unique_identifier() {
		$guid = \PressbooksLtiProvider\globally_unique_identifier(); // Book
		$this->assertRegExp( '/^\{?[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}\}?$/', $guid );
		$guid = \PressbooksLtiProvider\globally_unique_identifier( true ); // Site
		$this->assertRegExp( '/^\{?[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}\}?$/', $guid );
	}

	public function test_do_format() {
		// Book
		$this->_book();
		ob_start();
		\PressbooksLtiProvider\do_format( 'lti' );
		$buffer = ob_get_clean();
		$this->assertContains( 'Invalid or missing lti_message_type parameter', $buffer );

		// Root site
		$book_id = get_current_blog_id();
		switch_to_blog( get_network()->site_id );
		ob_start();
		\PressbooksLtiProvider\do_format( "lti/{$book_id}" );
		$buffer = ob_get_clean();
		$this->assertEmpty( $buffer );

		$_REQUEST['page_id'] = 123;
		ob_start();
		\PressbooksLtiProvider\do_format( "lti/{$book_id}" );
		$buffer = ob_get_clean();
		$this->assertContains( 'Invalid or missing lti_message_type parameter', $buffer );

		$_POST['resource_link_title'] = 'Book created from tool laucher';
		$_POST['resource_link_id'] = '1';
		$_POST['context_id'] = '2';
		$_POST['context_label'] = 'My context label';

		ob_start();
		\PressbooksLtiProvider\do_format( "lti/createbook" );
		$buffer = ob_get_clean();
		$this->assertContains( 'Invalid or missing lti_message_type parameter', $buffer );
	}

	public function test_session_relax() {
		\PressbooksLtiProvider\session_relax();
		$this->assertFalse( has_action( 'login_init', 'send_frame_options_header' ) );
		$this->assertFalse( has_action( 'admin_init', 'send_frame_options_header' ) );
		$this->assertFalse( has_action( 'wp_login', '\Pressbooks\session_kill' ) );
		$this->assertFalse( has_action( 'wp_logout', '\Pressbooks\session_kill' ) );
	}

	public function test_session_configuration() {
		$_SERVER['REQUEST_URI'] = '/contains/format/lti/something';
		$options = \PressbooksLtiProvider\session_configuration(
			[
				'read_and_close' => true,
				'some_other_setting' => true,
			]
		);
		$this->assertArrayNotHasKey( 'read_and_close', $options );
		$this->assertArrayHasKey( 'some_other_setting', $options );
	}

	public function test_login_errors() {
		$errors = new WP_Error();
		$errors = \PressbooksLtiProvider\login_errors( $errors, null );
		$this->assertEmpty( $errors->get_error_codes() );

		$storage = new \PressbooksLtiProvider\Entities\Storage();
		$_SESSION['pb_lti_prompt_for_authentication'] = $storage;
		$errors = \PressbooksLtiProvider\login_errors( $errors, null );
		$this->assertEquals( 'lti', $errors->get_error_codes()[0] );
		unset( $_SESSION['pb_lti_prompt_for_authentication'] );
	}

	public function test_blade() {
		$blade = \PressbooksLtiProvider\blade();
		$this->assertTrue( is_object( $blade ) );
	}

	public function test_is_json() {
		$this->assertFalse( \PressbooksLtiProvider\is_json( 'Nope' ) );
		$this->assertTrue( \PressbooksLtiProvider\is_json( '{ "Yes": 1 }' ) );
	}

	public function test_domain_and_path() {
		$happy_subdomain = \PressbooksLtiProvider\domain_and_path( 'https://subdomain.pressbooks.test/sub/' );
		$happy_path = \PressbooksLtiProvider\domain_and_path( 'https://pressbooks.test/happy' );
		$happy_no_path = \PressbooksLtiProvider\domain_and_path( 'https://pressbooks.test' );
		$this->assertEquals( 'subdomain.pressbooks.test', $happy_subdomain[0] );
		$this->assertEquals( '/sub/', $happy_subdomain[1] );
		$this->assertEquals( 'pressbooks.test', $happy_path[0] );
		$this->assertEquals( '/happy', $happy_path[1] );
		$this->assertEquals( '/', $happy_no_path[1] );
		$this->assertFalse( \PressbooksLtiProvider\domain_and_path( '/justapath/' ) );
	}

}
