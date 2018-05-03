<?php

namespace Pressbooks\Lti\Provider;

class Admin {

	const OPTION = 'pressbooks_lti';

	const OPTION_GUID = 'pressbooks_lti_GUID';

	/**
	 * @var Admin
	 */
	private static $instance = null;

	/**
	 * @return Admin
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	/**
	 * @param Admin $obj
	 */
	static public function hooks( Admin $obj ) {
		add_action( 'network_admin_menu', [ $obj, 'addConsumersMenu' ], 1000 );
		add_action( 'network_admin_menu', [ $obj, 'addSettingsMenu' ], 1000 );
	}

	/**
	 *
	 */
	public function __construct() {
	}

	/**
	 * Add LTI Consumers menu
	 */
	public function addConsumersMenu() {

		$parent_slug = \Pressbooks\Admin\Dashboard\init_network_integrations_menu();

		add_submenu_page(
			$parent_slug,
			__( 'LTI Consumers', 'pressbooks-lti-provider' ),
			__( 'LTI Consumers', 'pressbooks-lti-provider' ),
			'manage_network',
			'pb_lti_consumers',
			[ $this, 'printConsumersMenu' ]
		);
	}

	/**
	 *
	 */
	public function printConsumersMenu() {
		if ( ! class_exists( 'WP_List_Table' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
		}
		$table = new Table();
		echo '<div class="wrap">';
		echo '<h1>' . __( 'LTI Consumers', 'pressbooks-lti-provider' ) . '</h1>';
		$message = '';
		if ( 'delete' === $table->current_action() ) {
			/* translators: 1: Number of consumers deleted */
			$message = sprintf( __( 'Consumers deleted: %d', 'pressbooks-lti-provider' ), is_array( $_REQUEST['ID'] ) ? count( $_REQUEST['ID'] ) : 1 );
			$message = '<div id="message" class="updated notice is-dismissible"><p>' . $message . '</p></div>';
		}
		$table->prepare_items();
		echo $message;
		echo '<form id="pressbooks-lti-admin" method="GET">';
		echo '<input type="hidden" name="page" value="' . $_REQUEST['page'] . '" />';
		$table->display();
		echo '</form>';
		echo '</div>';
	}

	/**
	 * Add LTI Settings menu
	 */
	public function addSettingsMenu() {

		$parent_slug = \Pressbooks\Admin\Dashboard\init_network_integrations_menu();

		add_submenu_page(
			$parent_slug,
			__( 'LTI Settings', 'pressbooks-lti-provider' ),
			__( 'LTI Settings', 'pressbooks-lti-provider' ),
			'manage_network',
			'pb_lti_settings',
			[ $this, 'printSettingsMenu' ]
		);
	}

	/**
	 *
	 */
	public function printSettingsMenu() {
		if ( $this->saveSettings() ) {
			echo '<div id="message" class="updated notice is-dismissible"><p>' . __( 'Settings saved.' ) . '</p></div>';
		}
		$html = blade()->render(
			'settings', [
				'form_url' => network_admin_url( '/admin.php?page=pb_lti_settings' ),
				'options' => $this->getSettings(),
			]
		);
		echo $html;
	}

	/**
	 * @return bool
	 */
	public function saveSettings() {
		if ( ! empty( $_POST ) && check_admin_referer( 'pb-lti-provider' ) ) {
			$valid_roles = [ 'administrator', 'editor', 'author', 'contributor', 'subscriber', 'anonymous' ];
			$update = [
				'whitelist' => trim( $_POST['whitelist'] ),
				'admin_default' => in_array( $_POST['admin_default'], $valid_roles, true ) ? $_POST['admin_default'] : 'subscriber',
				'staff_default' => in_array( $_POST['staff_default'], $valid_roles, true ) ? $_POST['staff_default'] : 'subscriber',
				'learner_default' => in_array( $_POST['learner_default'], $valid_roles, true ) ? $_POST['learner_default'] : 'subscriber',
				'hide_navigation' => (int) $_POST['hide_navigation'],
			];
			$result = update_site_option( self::OPTION, $update );
			return $result;
		}
		return false;
	}

	/**
	 * @return array
	 */
	public function getSettings() {

		$options = get_site_option( self::OPTION, [] );

		if ( empty( $options['whitelist'] ) ) {
			$options['whitelist'] = '';
		}
		if ( empty( $options['admin_default'] ) ) {
			$options['admin_default'] = 'subscriber';
		}
		if ( empty( $options['staff_default'] ) ) {
			$options['staff_default'] = 'subscriber';
		}
		if ( empty( $options['learner_default'] ) ) {
			$options['learner_default'] = 'subscriber';
		}
		if ( empty( $options['hide_navigation'] ) ) {
			$options['hide_navigation'] = 0;
		}

		return $options;
	}

}