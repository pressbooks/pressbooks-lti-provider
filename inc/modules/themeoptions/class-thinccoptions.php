<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Lti\Provider\Modules\ThemeOptions;

use Pressbooks\Options;
use function \Pressbooks\Utility\getset;

class ThinCCOptions extends Options {

	/**
	 * The value for option: pressbooks_theme_options_thincc_version
	 *
	 * @see upgrade()
	 * @var int
	 */
	const VERSION = 1;

	/**
	 * Common Cartridge options.
	 *
	 * @var array
	 */
	public $options;

	/**
	 * Common Cartridge defaults.
	 *
	 * @var array
	 */
	public $defaults;

	/**
	 * Constructor.
	 *
	 * @param array $options
	 */
	function __construct( array $options ) {
		$this->options = $options;
		$this->defaults = $this->getDefaults();
		$this->booleans = $this->getBooleanOptions();
		$this->strings = $this->getStringOptions();
		$this->integers = $this->getIntegerOptions();
		$this->floats = $this->getFloatOptions();
		$this->predefined = $this->getPredefinedOptions();

		foreach ( $this->defaults as $key => $value ) {
			if ( ! isset( $this->options[ $key ] ) ) {
				$this->options[ $key ] = $value;
			}
		}
	}


	/**
	 * Configure the options page or tab using the settings API.
	 */
	public function init() {
		$_option = 'pressbooks_theme_options_' . $this->getSlug();
		$_page = $_option;
		$_section = $this->getSlug() . '_options_section';

		if ( false === get_option( $_option ) ) {
			add_option( $_option, $this->defaults );
		}

		add_settings_section(
			$_section,
			$this->getTitle(),
			[ $this, 'display' ],
			$_page
		);

		add_settings_field(
			'version',
			__( 'Common Cartridge Version', 'pressbooks-lti-provider' ),
			[ $this, 'renderCommonCartridgeVersion' ],
			$_page,
			$_section,
			[
				'1.2' => __( '1.2', 'pressbooks-lti-provider' ),
				'1.3' => __( '1.3', 'pressbooks-lti-provider' ),
			]
		);

		add_settings_field(
			'use_web_links',
			__( 'Links', 'pressbooks-lti-provider' ),
			[ $this, 'renderUseWebLinks' ],
			$_page,
			$_section,
			[
				__( 'Use normal web links instead of LTI links', 'pressbooks-lti-provider' ),
			]
		);

		add_settings_field(
			'include_topics',
			__( 'Topics', 'pressbooks-lti-provider' ),
			[ $this, 'renderIncludeTopics' ],
			$_page,
			$_section,
			[
				__( 'Create Discussion Topics (for pages starting with "Discussion:")', 'pressbooks-lti-provider' ),
			]
		);

		add_settings_field(
			'include_assignments',
			__( 'Assignments', 'pressbooks-lti-provider' ),
			[ $this, 'renderIncludeAssignments' ],
			$_page,
			$_section,
			[
				__( 'Create Assignments (for pages starting with "Assignment:")', 'pressbooks-lti-provider' ),
			]
		);

		register_setting(
			$_page,
			$_option,
			[ $this, 'sanitize' ]
		);
	}

	/**
	 * Render the version radio buttons.
	 *
	 * @param array $args
	 */
	public function renderCommonCartridgeVersion( $args ) {
		$this->renderRadioButtons(
			[
				'id' => 'version',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'version',
				'value' => getset( $this->options, 'version' ),
				'choices' => $args,
			]
		);
	}

	/**
	 * Render the use_web_links checkbox.
	 *
	 * @param array $args
	 */
	public function renderUseWebLinks( $args ) {
		$this->renderCheckbox(
			[
				'id' => 'use_web_links',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'use_web_links',
				'value' => getset( $this->options, 'use_web_links' ),
				'label' => $args[0],
			]
		);
	}

	/**
	 * Render the include_topics checkbox.
	 *
	 * @param array $args
	 */
	public function renderIncludeTopics( $args ) {
		$this->renderCheckbox(
			[
				'id' => 'include_topics',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'include_topics',
				'value' => getset( $this->options, 'include_topics' ),
				'label' => $args[0],
			]
		);
	}

	/**
	 * Render the include_assignments checkbox.
	 *
	 * @param array $args
	 */
	public function renderIncludeAssignments( $args ) {
		$this->renderCheckbox(
			[
				'id' => 'include_assignments',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'include_assignments',
				'value' => getset( $this->options, 'include_assignments' ),
				'label' => $args[0],
			]
		);
	}


	/**
	 * Display the options page or tab description.
	 */
	public function display() {
		echo '<p>' . __( 'These options apply to Common Cartridge exports.', 'pressbooks-lti-provider' ) . '</p>';
	}

	/**
	 * Render the options page or tab.
	 */
	public function render() {
		//  Not used
	}

	/**
	 * Upgrade handler for the options page or tab.
	 *
	 * @param int $version
	 */
	public function upgrade( $version ) {

	}

	/**
	 * Get the slug for this options page or tab.
	 *
	 * @return string $slug
	 */
	static public function getSlug() {
		return 'thincc';
	}

	/**
	 * Get the localized title of this options page or tab.
	 *
	 * @return string $title
	 */
	static public function getTitle() {
		return __( 'Common Cartridge Options', 'pressbooks-lti-provider' );
	}

	/**
	 * Get an array of default values for this set of options
	 *
	 * @return array $defaults
	 */
	static public function getDefaults() {
		/**
		 * @since 1.0.0
		 *
		 * @param array $value
		 */
		return apply_filters(
			'pb_theme_options_thincc_defaults', [
				'version' => '1.2',
				'use_web_links' => 0,
				'include_topics' => 0,
				'include_assignments' => 0,
			]
		);
	}

	/**
	 * Get an array of options which return booleans.
	 *
	 * @return array $options
	 */
	static function getBooleanOptions() {
		/**
		 * @since 1.0.0
		 *
		 * @param array $value
		 */
		return apply_filters(
			'pb_theme_options_thincc_booleans', [
				'use_web_links',
				'include_topics',
				'include_assignments',
			]
		);
	}

	/**
	 * Get an array of options which return strings.
	 *
	 * @return array $options
	 */
	static function getStringOptions() {
		/**
		 * @since 1.0.0
		 *
		 * @param array $value
		 */
		return apply_filters(
			'pb_theme_options_thincc_strings', []
		);
	}

	/**
	 * Get an array of options which return integers.
	 *
	 * @return array $options
	 */
	static function getIntegerOptions() {
		/**
		 * @since 1.0.0
		 *
		 * @param array $value
		 */
		return apply_filters(
			'pb_theme_options_thincc_integers', []
		);
	}

	/**
	 * Get an array of options which return floats.
	 *
	 * @return array $options
	 */
	static function getFloatOptions() {
		/**
		 * @since 1.0.0
		 *
		 * @param array $value
		 */
		return apply_filters(
			'pb_theme_options_thincc_floats', []
		);
	}

	/**
	 * Get an array of options which return predefined values.
	 *
	 * @return array $options
	 */
	static function getPredefinedOptions() {
		/**
		 * @since 1.0.0
		 *
		 * @param array $value
		 */
		return apply_filters(
			'pb_theme_options_thincc_predefined', [
				'version',
			]
		);
	}

	/**
	 * Filter the array of default values for this set of options
	 *
	 * @param array $defaults
	 *
	 * @return array $defaults
	 */
	static public function filterDefaults( $defaults ) {
		return $defaults;
	}
}
