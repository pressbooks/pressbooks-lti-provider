<?php

class AdminTest extends \WP_UnitTestCase {

	/**
	 * @var \PressbooksLtiProvider\Admin
	 */
	protected $admin;

	/**
	 *
	 */
	public function set_up() {
		parent::set_up();
		$GLOBALS['hook_suffix'] = 'mock';
		$this->admin = new \PressbooksLtiProvider\Admin();
	}

	public function test_exportFormats() {
		$formats = $this->admin->exportFormats( [] );
		$this->assertTrue( isset( $formats['exotic']['thincc13'] ) ); // The default

		update_option( \PressbooksLtiProvider\Admin::OPTION, [ 'cc_version' => '1.2' ] );
		$formats = $this->admin->exportFormats( [] );
		$this->assertTrue( isset( $formats['exotic']['thincc12'] ) );

		update_option( \PressbooksLtiProvider\Admin::OPTION, [ 'cc_version' => '1.1' ] );
		$formats = $this->admin->exportFormats( [] );
		$this->assertTrue( isset( $formats['exotic']['thincc11'] ) );

		update_option( \PressbooksLtiProvider\Admin::OPTION, [ 'cc_version' => 'all' ] );
		$formats = $this->admin->exportFormats( [] );
		$this->assertTrue( isset( $formats['exotic']['thincc13'] ) );
		$this->assertTrue( isset( $formats['exotic']['thincc12'] ) );
		$this->assertTrue( isset( $formats['exotic']['thincc11'] ) );

		delete_option( \PressbooksLtiProvider\Admin::OPTION );
	}

	public function test_fileTypeNames() {
		$formats = $this->admin->fileTypeNames( [] );
		$this->assertTrue( isset( $formats['imscc'] ) );
	}

	public function test_exportFileFormats() {
		$formats = $this->admin->exportFileFormats( [] );
		$this->assertTrue( isset( $formats['thincc13'] ) ); // The default

		update_option( \PressbooksLtiProvider\Admin::OPTION, [ 'cc_version' => '1.2' ] );
		$formats = $this->admin->exportFileFormats( [] );
		$this->assertTrue( isset( $formats['thincc12'] ) );

		update_option( \PressbooksLtiProvider\Admin::OPTION, [ 'cc_version' => '1.1' ] );
		$formats = $this->admin->exportFileFormats( [] );
		$this->assertTrue( isset( $formats['thincc11'] ) );

		delete_option( \PressbooksLtiProvider\Admin::OPTION );
	}

	public function test_activeExportModules() {
		$modules = $this->admin->activeExportModules( [] );
		$this->assertEmpty( $modules );

		$_POST['export_formats']['thincc11'] = true;
		$_POST['export_formats']['thincc12'] = true;
		$_POST['export_formats']['thincc13'] = true;
		$modules = $this->admin->activeExportModules( $modules );
		$this->assertTrue( array_search( '\PressbooksLtiProvider\Modules\Export\ThinCC\CommonCartridge11', $modules, true ) !== false );
		$this->assertTrue( array_search( '\PressbooksLtiProvider\Modules\Export\ThinCC\CommonCartridge12', $modules, true ) !== false );
		$this->assertTrue( array_search( '\PressbooksLtiProvider\Modules\Export\ThinCC\CommonCartridge13', $modules, true ) !== false );
	}

	public function test_getExportFileClass() {
		$this->assertEquals( 'unknown', $this->admin->getExportFileClass( 'unknown' ) );
		$this->assertEquals( 'imscc', $this->admin->getExportFileClass( 'imscc' ) ); // TODO
	}

	public function test_hideNavigation() {
		ob_start();
		$this->admin->hideNavigation();
		$buffer = ob_get_clean();
		$this->assertContains( 'no-navigation', $buffer );
		$this->assertContains( '</script>', $buffer );
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
		$this->assertEquals( $options['prompt_for_authentication'], 0 );
		$this->assertEquals( $options['book_override'], 1 );
		$this->assertEquals( $options['admin_default'], 'subscriber' );
		$this->assertEquals( $options['staff_default'], 'subscriber' );
		$this->assertEquals( $options['learner_default'], 'subscriber' );
		$this->assertEquals( $options['hide_navigation'], 0 );
		$this->assertEquals( $options['cc_version'], '1.3' );

		$_REQUEST['_wpnonce'] = wp_create_nonce( 'pb-lti-provider' );
		$_POST = [
			'whitelist' => "pressbooks.com\npressbooks.education",
			'prompt_for_authentication' => 1,
			'book_override' => 0,
			'admin_default' => 'administrator',
			'staff_default' => 'editor',
			'learner_default' => 'contributor',
			'hide_navigation' => 1,
			'cc_version' => '1.2',
		];
		$this->admin->saveSettings();
		$options = $this->admin->getSettings();

		$this->assertEquals( $options['whitelist'], "pressbooks.com\npressbooks.education" );
		$this->assertEquals( $options['prompt_for_authentication'], 1 );
		$this->assertEquals( $options['book_override'], 0 );
		$this->assertEquals( $options['admin_default'], 'administrator' );
		$this->assertEquals( $options['staff_default'], 'editor' );
		$this->assertEquals( $options['learner_default'], 'contributor' );
		$this->assertEquals( $options['hide_navigation'], 1 );
		$this->assertEquals( $options['cc_version'], 1.2 );
	}

	//

	public function test_addBookSettingsMenu() {
		update_site_option( \PressbooksLtiProvider\Admin::OPTION, [ 'book_override' => 1 ] );
		$this->admin->addBookSettingsMenu();
		$this->assertTrue( true ); // Did not crash
		delete_site_option( \PressbooksLtiProvider\Admin::OPTION );
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
		$this->assertTrue( ! isset( $options['prompt_for_authentication'] ) );
		$this->assertTrue( ! isset( $options['book_override'] ) );
		$this->assertEquals( $options['admin_default'], 'subscriber' );
		$this->assertEquals( $options['staff_default'], 'subscriber' );
		$this->assertEquals( $options['learner_default'], 'subscriber' );
		$this->assertEquals( $options['hide_navigation'], 0 );
		$this->assertEquals( $options['cc_version'], '1.3' );

		$_REQUEST['_wpnonce'] = wp_create_nonce( 'pb-lti-provider-book' );
		$_POST = [
			'whitelist' => "pressbooks.com\npressbooks.education",
			'prompt_for_authentication' => 1,
			'book_override' => 1,
			'admin_default' => 'administrator',
			'staff_default' => 'editor',
			'learner_default' => 'contributor',
			'hide_navigation' => 1,
			'cc_version' => '1.2',
		];
		$this->admin->saveBookSettings();
		$options = $this->admin->getBookSettings();

		$this->assertTrue( ! isset( $options['whitelist'] ) );
		$this->assertTrue( ! isset( $options['prompt_for_authentication'] ) );
		$this->assertTrue( ! isset( $options['book_override'] ) );
		$this->assertEquals( $options['admin_default'], 'administrator' );
		$this->assertEquals( $options['staff_default'], 'editor' );
		$this->assertEquals( $options['learner_default'], 'contributor' );
		$this->assertEquals( $options['hide_navigation'], 1 );
		$this->assertEquals( $options['cc_version'], '1.2' );

		// Disallow book override after the fact
		$site_options = get_site_option( \PressbooksLtiProvider\Admin::OPTION );
		$site_options['book_override'] = 0;
		update_site_option( \PressbooksLtiProvider\Admin::OPTION, $site_options );
		$options = $this->admin->getBookSettings();
		$this->assertTrue( ! isset( $options['whitelist'] ) );
		$this->assertTrue( ! isset( $options['book_override'] ) );
		$this->assertEquals( $options['admin_default'], 'subscriber' );
		$this->assertEquals( $options['staff_default'], 'subscriber' );
		$this->assertEquals( $options['learner_default'], 'subscriber' );
		$this->assertEquals( $options['hide_navigation'], 0 );
		$this->assertEquals( $options['cc_version'], '1.3' );
	}

}
