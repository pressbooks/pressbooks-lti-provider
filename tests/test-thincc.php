<?php

class ThinCCTest extends \WP_UnitTestCase {

	/**
	 * @var \PressbooksLtiProvider\Modules\Export\ThinCC\CommonCartridge12
	 */
	protected $thincc11;


	/**
	 * @var \PressbooksLtiProvider\Modules\Export\ThinCC\CommonCartridge12
	 */
	protected $thincc12;

	/**
	 * @var \PressbooksLtiProvider\Modules\Export\ThinCC\CommonCartridge13
	 */
	protected $thincc13;

	use utilsTrait;

	/**
	 *
	 */
	public function setUp() {
		parent::setUp();
		$this->thincc11 = new PressbooksLtiProvider\Modules\Export\ThinCC\CommonCartridge12( [] );
		$this->thincc12 = new PressbooksLtiProvider\Modules\Export\ThinCC\CommonCartridge12( [] );
		$this->thincc13 = new PressbooksLtiProvider\Modules\Export\ThinCC\CommonCartridge13( [] );
	}

	public function test_sanityCheckExports() {
		$this->_book();

		$this->assertTrue( $this->thincc11->convert(), "Could not convert with CommonCartridge11" );
		$this->assertTrue( $this->thincc11->validate(), "Could not validate with CommonCartridge11" );

		$this->assertTrue( $this->thincc12->convert(), "Could not convert with CommonCartridge12" );
		$this->assertTrue( $this->thincc12->validate(), "Could not validate with CommonCartridge12" );

		$this->assertTrue( $this->thincc13->convert(), "Could not convert with CommonCartridge13" );
		$this->assertTrue( $this->thincc13->validate(), "Could not validate with CommonCartridge13" );
	}

	public function test_deleteTmpDir() {
		$this->assertTrue( file_exists( $this->thincc12->getTmpDir() ) );
		$this->thincc12->deleteTmpDir();
		$this->assertFalse( file_exists( $this->thincc12->getTmpDir() ) );
	}

	public function test_createManifest() {
		$this->_book();
		$this->thincc12->createManifest();
		$this->assertTrue( file_exists( $this->thincc12->getTmpDir() . '/imsmanifest.xml' ) );
	}

	public function test_identifiers() {
		$this->_book();
		$xml = $this->thincc12->identifiers();
		$this->assertContains( '</item>', $xml );
		$this->assertContains( 'identifier=', $xml );
		$this->assertContains( 'identifierref=', $xml );
	}

	public function test_resources() {
		$this->_book();
		$xml = $this->thincc12->resources();
		$this->assertContains( '</resource>', $xml );
		$this->assertContains( '<file href=', $xml );
	}

	public function test_createResources() {
		$this->_book();
		$this->thincc12->createResources();
		foreach ( scandir( $this->thincc12->getTmpDir() ) as $file ) {
			if ( substr( $file, 0, 2 ) === 'R_' && preg_match( '/\.xml/', $file ) ) {
				// At least one resource was created
				$this->assertTrue( true );
				return;
			}
		}
		$this->fail();
	}

	public function test_isAssignment() {
		$this->assertFalse( $this->thincc13->isAssignment( 0, 'Hello' ) );
		$this->assertTrue( $this->thincc13->isAssignment( 0, 'Assignment: Hello' ) );
	}

}