<?php

class NamespaceTest extends \WP_UnitTestCase {


	/**
	 * Test PB style class initializations
	 */
	public function test_classInitConventions() {
		$classes = [
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

	public function test_blade() {
		$blade = \Pressbooks\Lti\Provider\blade();
		$this->assertTrue( is_object( $blade ) );
	}

}