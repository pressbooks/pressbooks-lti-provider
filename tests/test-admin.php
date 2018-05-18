<?php

class AdminTest extends \WP_UnitTestCase {

	/**
	 * @var \Pressbooks\Lti\Provider\Admin
	 */
	protected $admin;

	/**
	 *
	 */
	public function setUp() {
		parent::setUp();
		$GLOBALS['hook_suffix'] = 'mock';
		$this->admin = new \Pressbooks\Lti\Provider\Admin();
	}

	public function test_exportFormats() {
		$formats = $this->admin->exportFormats( [] );
		$this->assertTrue( isset( $formats['exotic']['thincc12'] ) );
		$this->assertTrue( isset( $formats['exotic']['thincc13'] ) );
	}

	public function test_activeExportModules() {
		$modules = $this->admin->activeExportModules( [] );
		$this->assertEmpty( $modules );

		$_POST['export_formats']['thincc12'] = true;
		$_POST['export_formats']['thincc13'] = true;
		$modules = $this->admin->activeExportModules( $modules );
		$this->assertTrue( array_search( '\Pressbooks\Lti\Provider\Modules\Export\ThinCC\CommonCartridge12', $modules, true ) !== false );
		$this->assertTrue( array_search( '\Pressbooks\Lti\Provider\Modules\Export\ThinCC\CommonCartridge13', $modules, true ) !== false );
	}

	public function test_getExportFileClass() {
		$this->assertEquals( 'unknown', $this->admin->getExportFileClass( 'unknown' ) );
	}

	public function test_hideNavigation() {
		ob_start();
		$this->admin->hideNavigation();
		$buffer = ob_get_clean();
		$this->assertContains( 'no-navigation', $buffer );
		$this->assertContains( '</script>', $buffer );
	}

	public function test_addConsumersHeader() {
		ob_start();
		$this->admin->addConsumersHeader();
		$buffer = ob_get_clean();
		$this->assertEmpty( $buffer );

		$_GET['page'] = 'pb_lti_consumers';
		ob_start();
		$this->admin->addConsumersHeader();
		$buffer = ob_get_clean();
		$this->assertContains( '</style>', $buffer );
	}

	public function test_addConsumersMenu() {
		$this->admin->addConsumersMenu();
		$this->assertTrue( true ); // Did not crash
	}

	public function test_handleConsumerActions() {
		ob_start();
		$this->admin->handleConsumerActions();
		$buffer = ob_get_clean();
		$this->assertNotEmpty( $buffer );
	}

	public function test_printConsumersMenu() {
		ob_start();
		$this->admin->printConsumersMenu();
		$buffer = ob_get_clean();
		$this->assertContains( '</form>', $buffer );
	}

	public function test_printConsumerForm() {
		ob_start();
		$this->admin->printConsumerForm();
		$buffer = ob_get_clean();
		$this->assertContains( '</form>', $buffer );
	}

	public function test_ConsumerOptions() {
		// TODO
	}

	public function test_addSettingsMenu() {
		$this->admin->addSettingsMenu();
		$this->assertTrue( true ); // Did not crash
	}

	public function test_printSettingsMenu() {
		ob_start();
		$this->admin->printSettingsMenu();
		$buffer = ob_get_clean();
		$this->assertContains( '</form>', $buffer );
	}

	public function test_settings() {

		$options = $this->admin->getSettings();

		$this->assertEquals( $options['whitelist'], '' );
		$this->assertEquals( $options['admin_default'], 'subscriber' );
		$this->assertEquals( $options['staff_default'], 'subscriber' );
		$this->assertEquals( $options['learner_default'], 'subscriber' );
		$this->assertEquals( $options['hide_navigation'], 0 );

		$_REQUEST['_wpnonce'] = wp_create_nonce( 'pb-lti-provider' );
		$_POST = [
			'whitelist' => "pressbooks.com\npressbooks.education",
			'admin_default' => 'administrator',
			'staff_default' => 'editor',
			'learner_default' => 'contributor',
			'hide_navigation' => 1,
		];
		$this->admin->saveSettings();
		$options = $this->admin->getSettings();

		$this->assertEquals( $options['whitelist'], "pressbooks.com\npressbooks.education" );
		$this->assertEquals( $options['admin_default'], 'administrator' );
		$this->assertEquals( $options['staff_default'], 'editor' );
		$this->assertEquals( $options['learner_default'], 'contributor' );
		$this->assertEquals( $options['hide_navigation'], 1 );
	}

	//

	public function test_addBookSettingsMenu() {
		$this->admin->addBookSettingsMenu();
		$this->assertTrue( true ); // Did not crash
	}

	public function test_addOptionsTab() {
		$tabs = $this->admin->addOptionsTab( [] );
		$this->assertArrayHasKey( 'thincc', $tabs );
	}

	public function test_printBookSettingsMenu() {
		ob_start();
		$this->admin->printBookSettingsMenu();
		$buffer = ob_get_clean();
		$this->assertContains( '</form>', $buffer );
	}

	public function test_bookSettings() {

		$options = $this->admin->getBookSettings();

		$this->assertTrue( ! isset( $options['whitelist'] ) );
		$this->assertEquals( $options['admin_default'], 'subscriber' );
		$this->assertEquals( $options['staff_default'], 'subscriber' );
		$this->assertEquals( $options['learner_default'], 'subscriber' );
		$this->assertEquals( $options['hide_navigation'], 0 );

		$_REQUEST['_wpnonce'] = wp_create_nonce( 'pb-lti-provider-book' );
		$_POST = [
			'whitelist' => "pressbooks.com\npressbooks.education",
			'admin_default' => 'administrator',
			'staff_default' => 'editor',
			'learner_default' => 'contributor',
			'hide_navigation' => 1,
		];
		$this->admin->saveBookSettings();
		$options = $this->admin->getBookSettings();

		$this->assertTrue( ! isset( $options['whitelist'] ) );
		$this->assertEquals( $options['admin_default'], 'administrator' );
		$this->assertEquals( $options['staff_default'], 'editor' );
		$this->assertEquals( $options['learner_default'], 'contributor' );
		$this->assertEquals( $options['hide_navigation'], 1 );
	}

}