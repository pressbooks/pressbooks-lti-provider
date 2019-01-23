<?php

class UpdatesTest extends \WP_UnitTestCase {

	/**
	 * @var \Pressbooks\LTI\Provider\Updates
	 */
	protected $updates;

	/**
	 *
	 */
	public function setUp() {
		parent::setUp();
		$this->updates = new \Pressbooks\LTI\Provider\Updates();
	}

	public function test_gitHubUpdater() {
		$this->updates->gitHubUpdater();
		$this->assertTrue( has_filter( 'puc_is_slug_in_use-pressbooks-lti-provider' ) );
	}


}