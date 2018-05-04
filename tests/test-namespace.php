<?php

class NamespaceTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * Test PB style class initializations
	 */
	public function test_classInitConventions() {
		$classes = [
			'\Pressbooks\Lti\Provider\Admin',
			'\Pressbooks\Lti\Provider\Updates',
		];
		foreach ( $classes as $class ) {
			$result = $class::init();
			$this->assertInstanceOf( $class, $result );
		}
	}

	public function test_globally_unique_identifier() {
		$guid = \Pressbooks\Lti\Provider\globally_unique_identifier();
		$this->assertRegExp( '/^\{?[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}\}?$/', $guid );
	}

	public function test_do_format() {
		$this->_book();
		ob_start();
		\Pressbooks\Lti\Provider\do_format( 'lti' );
		$buffer = ob_get_clean();
		$this->assertContains( 'Invalid or missing lti_message_type parameter', $buffer );
	}

	public function test_blade() {
		$blade = \Pressbooks\Lti\Provider\blade();
		$this->assertTrue( is_object( $blade ) );
	}

	public function is_json() {
		$this->assertFalse( \Pressbooks\Lti\Provider\is_json( 'Nope' ) );
		$this->assertTrue( \Pressbooks\Lti\Provider\is_json( '{ "Yes": 1 }' ) );
	}

}