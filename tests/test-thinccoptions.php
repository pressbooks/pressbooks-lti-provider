<?php

class ThinCCOptions extends \WP_UnitTestCase {

	/**
	 * @var \Pressbooks\Lti\Provider\Modules\ThemeOptions\ThinCCOptions
	 */
	protected $options;

	use utilsTrait;

	/**
	 *
	 */
	public function setUp() {
		parent::setUp();
		$this->options = new \Pressbooks\Lti\Provider\Modules\ThemeOptions\ThinCCOptions( [] );
	}

	public function test_init() {

		global $wp_settings_fields;

		$page = 'pressbooks_theme_options_' . $this->options->getSlug();
		$section = $this->options->getSlug() . '_options_section';

		$this->assertFalse( isset( $wp_settings_fields[ $page ] ) );
		$this->options->init();
		$this->assertTrue( isset( $wp_settings_fields[ $page ] ) );
		$this->assertTrue( isset( $wp_settings_fields[ $page ][ $section ] ) );
	}

	public function test_renderCommonCartridgeVersion() {
		$option = 'pressbooks_theme_options_' . $this->options->getSlug();
		ob_start();
		$this->options->renderCommonCartridgeVersion( [ 1 => 'a', 2 => 'b' ] );
		$buffer = ob_get_clean();
		$this->assertContains( "{$option}[version]", $buffer );
		$this->assertContains( '<input ', $buffer );
		$this->assertContains( 'type="radio"', $buffer );
	}

	public function test_renderUseWebLinks() {
		$option = 'pressbooks_theme_options_' . $this->options->getSlug();
		ob_start();
		$this->options->renderUseWebLinks( [ 'a' ] );
		$buffer = ob_get_clean();
		$this->assertContains( "{$option}[use_web_links]", $buffer );
		$this->assertContains( '<input ', $buffer );
		$this->assertContains( 'type="checkbox"', $buffer );
	}

	public function test_renderIncludeTopics() {
		$option = 'pressbooks_theme_options_' . $this->options->getSlug();
		ob_start();
		$this->options->renderIncludeTopics( [ 'a' ] );
		$buffer = ob_get_clean();
		$this->assertContains( "{$option}[include_topics]", $buffer );
		$this->assertContains( '<input ', $buffer );
		$this->assertContains( 'type="checkbox"', $buffer );
	}

	public function test_renderIncludeAssignments() {
		$option = 'pressbooks_theme_options_' . $this->options->getSlug();
		ob_start();
		$this->options->renderIncludeAssignments( [ 'a' ] );
		$buffer = ob_get_clean();
		$this->assertContains( "{$option}[include_assignments]", $buffer );
		$this->assertContains( '<input ', $buffer );
		$this->assertContains( 'type="checkbox"', $buffer );
	}

	public function test_display() {
		ob_start();
		$this->options->display();
		$buffer = ob_get_clean();
		$this->assertContains( ' Common Cartridge', $buffer );
	}

	public function test_getSlug() {
		$this->assertEquals( 'thincc', $this->options::getSlug() );
	}

	public function test_getTitle() {
		$this->assertContains( 'Common Cartridge', $this->options::getTitle() );
	}

	public function test_getDefaults() {
		$this->assertTrue( is_array( $this->options::getDefaults() ) );
	}

	public function test_getBooleanOptions() {
		$this->assertTrue( is_array( $this->options::getBooleanOptions() ) );
	}

	public function test_getStringOptions() {
		$this->assertTrue( is_array( $this->options::getStringOptions() ) );
	}

	public function test_getIntegerOptions() {
		$this->assertTrue( is_array( $this->options::getIntegerOptions() ) );
	}

	public function test_getFloatOptions() {
		$this->assertTrue( is_array( $this->options::getFloatOptions() ) );
	}

	public function test_getPredefinedOptions() {
		$this->assertTrue( is_array( $this->options::getPredefinedOptions() ) );
	}

	public function test_filterDefaults() {
		$this->assertTrue( is_array( $this->options::filterDefaults( [] ) ) );
	}
}