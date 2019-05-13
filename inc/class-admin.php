<?php

namespace PressbooksLtiProvider;

use IMSGlobal\LTI\ToolProvider;
use PressbooksMix\Assets;
use Pressbooks\Book;

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
		load_plugin_textdomain( 'pressbooks-lti-provider', false, 'pressbooks-lti-provider/languages/' );

		add_action( 'network_admin_menu', [ $obj, 'addConsumersMenu' ], 1000 );
		add_action( 'network_admin_menu', [ $obj, 'addSettingsMenu' ], 1000 );
		add_action( 'admin_enqueue_scripts', [ $obj, 'enqueueScriptsAndStyles' ] );

		if ( Book::isBook() ) {
			if ( $obj->getBookSettings()['hide_navigation'] ) {
				add_action( 'wp_head', [ $obj, 'hideNavigation' ] );
			}
			add_action( 'admin_menu', [ $obj, 'addBookSettingsMenu' ] );
			add_filter( 'pb_export_formats', [ $obj, 'exportFormats' ] );
			add_filter( 'pb_export_filetype_names', [ $obj, 'fileTypeNames' ] );
			add_filter( 'pb_latest_export_filetypes', [ $obj, 'exportFileFormats' ] );
			add_filter( 'pb_active_export_modules', [ $obj, 'activeExportModules' ] );
			add_filter( 'pb_get_export_file_class', [ $obj, 'getExportFileClass' ] );
		}
	}

	/**
	 *
	 */
	public function __construct() {
	}

	/**
	 * @param array $formats
	 *
	 * @return array
	 */
	public function exportFormats( $formats ) {
		$version = (string) $this->getBookSettings()['cc_version'];
		if ( 'all' === $version ) {
			$formats['exotic']['thincc13'] = __( 'Common Cartridge 1.3 (LTI Links)', 'pressbooks-lti-provider' );
			$formats['exotic']['thincc12'] = __( 'Common Cartridge 1.2 (LTI Links)', 'pressbooks-lti-provider' );
			$formats['exotic']['thincc11'] = __( 'Common Cartridge 1.1 (LTI Links)', 'pressbooks-lti-provider' );
		} elseif ( '1.3' === $version ) {
			$formats['exotic']['thincc13'] = __( 'Common Cartridge 1.3 (LTI Links)', 'pressbooks-lti-provider' );
		} elseif ( '1.2' === $version ) {
			$formats['exotic']['thincc12'] = __( 'Common Cartridge 1.2 (LTI Links)', 'pressbooks-lti-provider' );
		} else {
			$formats['exotic']['thincc11'] = __( 'Common Cartridge 1.1 (LTI Links)', 'pressbooks-lti-provider' );
		}
		return $formats;
	}

	/**
	 * @param array $formats
	 *
	 * @return mixed
	 */
	public function fileTypeNames( $formats ) {
		$formats['imscc'] = __( 'LTI Links', 'pressbooks-lti-provider' );
		return $formats;
	}

	/**
	 * @param array $formats
	 *
	 * @return array
	 */
	public function exportFileFormats( $formats ) {
		$version = (string) $this->getBookSettings()['cc_version'];
		if ( 'all' === $version ) {
			$formats['thincc13'] = '._1_3.imscc';
			$formats['thincc12'] = '._1_2.imscc';
			$formats['thincc11'] = '._1_1.imscc';
		} elseif ( '1.3' === $version ) {
			$formats['thincc13'] = '._1_3.imscc';
		} elseif ( '1.2' === $version ) {
			$formats['thincc12'] = '._1_2.imscc';
		} else {
			$formats['thincc11'] = '._1_1.imscc';
		}
		return $formats;
	}

	/**
	 * @param array $modules
	 *
	 * @return array
	 */
	public function activeExportModules( $modules ) {
		if ( isset( $_POST['export_formats']['thincc11'] ) ) { // @codingStandardsIgnoreLine
			$modules[] = '\PressbooksLtiProvider\Modules\Export\ThinCC\CommonCartridge11';
		}
		if ( isset( $_POST['export_formats']['thincc12'] ) ) { // @codingStandardsIgnoreLine
			$modules[] = '\PressbooksLtiProvider\Modules\Export\ThinCC\CommonCartridge12';
		}
		if ( isset( $_POST['export_formats']['thincc13'] ) ) { // @codingStandardsIgnoreLine
			$modules[] = '\PressbooksLtiProvider\Modules\Export\ThinCC\CommonCartridge13';
		}
		return $modules;
	}

	/**
	 * @param string $file_extension
	 *
	 * @return string
	 */
	public function getExportFileClass( $file_extension ) {
		if ( 'imscc' === $file_extension ) {
			return 'imscc';
		}
		return $file_extension;
	}

	/**
	 * Hooked into `wp_head`
	 */
	public function hideNavigation() {
		$js = <<< EOJS
<script>
if ( window.top !== window.self ) {
	document.addEventListener( 'DOMContentLoaded', function () {
        document.body.classList.add( 'no-navigation' );
	} );
}
</script>
EOJS;
		echo $js;
	}

	/**
	 * Add styles for WP_List_Table and Exports page.
	 */
	public function enqueueScriptsAndStyles( $hook ) {
		$assets = new Assets( 'pressbooks-lti-provider', 'plugin' );
		if ( $hook === 'integrations_page_pb_lti_consumers' ) {
			wp_enqueue_style( 'pressbooks-lti-consumers', $assets->getPath( 'styles/pressbooks-lti-consumers.css' ), false, null );
		}
		if ( $hook === 'toplevel_page_pb_export' ) {
			wp_enqueue_style( 'pressbooks-cc-exports', $assets->getPath( 'styles/pressbooks-cc-exports.css' ), false, null );
		}
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
			[ $this, 'handleConsumerActions' ]
		);
	}

	/**
	 * Do something about LTI Consumers
	 */
	public function handleConsumerActions() {
		$action = $_REQUEST['action'] ?? null;
		if ( $action === 'edit' ) {
			$this->printConsumerForm();
		} else {
			$this->printConsumersMenu();
		}
	}

	/**
	 * Print LTI Consumer listing
	 */
	public function printConsumersMenu() {
		if ( ! class_exists( 'WP_List_Table' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
		}
		$page = 'pb_lti_consumers';
		$table = new Table();
		echo '<div class="wrap">';
		echo '<h1 class="wp-heading-inline">' . __( 'LTI Consumers', 'pressbooks-lti-provider' ) . '</h1>';
		$add_new_url = sprintf( '/admin.php?page=%s&action=edit', $page );
		$add_new_url = network_admin_url( $add_new_url );
		echo '<a class="page-title-action" href="' . $add_new_url . '">' . __( 'Add new', 'pressbooks-lti-provider' ) . '</a>';
		echo '<hr class="wp-header-end">';
		$message = '';
		if ( 'delete' === $table->current_action() ) {
			/* translators: 1: Number of consumers deleted */
			$message = sprintf( __( 'Consumers deleted: %d', 'pressbooks-lti-provider' ), is_array( $_REQUEST['ID'] ) ? count( $_REQUEST['ID'] ) : 1 );
			$message = '<div id="message" role="status" class="updated notice is-dismissible"><p>' . $message . '</p></div>';
		}
		$table->prepare_items();
		echo $message;
		echo '<form id="pressbooks-lti-admin" method="GET">';
		echo '<input type="hidden" name="page" value="' . $page . '" />';
		$table->display();
		echo '</form>';
		echo '</div>';
	}

	/**
	 * Print LTI Consumer Form
	 */
	public function printConsumerForm() {
		if ( $this->saveConsumer() ) {
			echo '<div id="message" role="status" class="updated notice is-dismissible"><p>' . __( 'Consumer saved.' ) . '</p></div>';
		}

		$id = (int) ( $_REQUEST['ID'] ?? $_REQUEST['id'] ?? 0 );

		$data_connector = Database::getConnector();
		if ( $id ) {
			$consumer = ToolProvider\ToolConsumer::fromRecordId( $id, $data_connector );
		} else {
			$consumer = new ToolProvider\ToolConsumer( null, $data_connector );
		}

		$options = [
			'ID' => $consumer->getRecordId(),
			'name' => $consumer->name,
			'key' => $consumer->getKey(),
			'secret' => $consumer->secret,
			'enabled' => $consumer->enabled,
			'enable_from' => $consumer->enableFrom ? date( 'Y-m-d', $consumer->enableFrom ) : '',
			'enable_until' => $consumer->enableUntil ? date( 'Y-m-d', $consumer->enableUntil ) : '',
			'protected' => $consumer->protected,
		];
		$html = blade()->render(
			'network.consumer', [
				'form_url' => network_admin_url( '/admin.php?page=pb_lti_consumers&action=edit' ),
				'back_url' => network_admin_url( '/admin.php?page=pb_lti_consumers' ),
				'options' => $options,
			]
		);
		echo $html;
	}

	/**
	 * @return bool
	 */
	public function saveConsumer() {
		if ( ! empty( $_POST ) && check_admin_referer( 'pb-lti-provider' ) ) {

			$id = (int) ( $_REQUEST['ID'] ?? $_REQUEST['id'] ?? 0 );

			$data_connector = Database::getConnector();
			if ( $id ) {
				$consumer = ToolProvider\ToolConsumer::fromRecordId( $id, $data_connector );
			} else {
				$consumer = new ToolProvider\ToolConsumer( $_POST['key'], $data_connector );
				$consumer->ltiVersion = ToolProvider\ToolProvider::LTI_VERSION1;
			}

			$consumer->name = trim( $_POST['name'] );
			$consumer->secret = trim( $_POST['secret'] );
			$consumer->enabled = ! empty( $_POST['enabled'] ) ? true : false;
			$consumer->protected = ! empty( $_POST['protected'] ) ? true : false;

			$date_from = $_POST['enable_from'];
			if ( empty( $date_from ) ) {
				$consumer->enableFrom = null;
			} else {
				$consumer->enableFrom = strtotime( $date_from );
			}

			$date_to = $_POST['enable_until'];
			if ( empty( $date_to ) ) {
				$consumer->enableUntil = null;
			} else {
				$consumer->enableUntil = strtotime( $date_to );
			}

			if ( $consumer->save() ) {
				$_REQUEST['ID'] = $consumer->getRecordId();
				return true;
			}
		}
		return false;
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
	 * Print LTI Settings Form
	 */
	public function printSettingsMenu() {
		if ( $this->saveSettings() ) {
			echo '<div id="message" role="status" class="updated notice is-dismissible"><p>' . __( 'Settings saved.' ) . '</p></div>';
		}
		$html = blade()->render(
			'network.settings', [
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
				'prompt_for_authentication' => (int) $_POST['prompt_for_authentication'],
				'book_override' => (int) $_POST['book_override'],
				'admin_default' => in_array( $_POST['admin_default'], $valid_roles, true ) ? $_POST['admin_default'] : 'subscriber',
				'staff_default' => in_array( $_POST['staff_default'], $valid_roles, true ) ? $_POST['staff_default'] : 'subscriber',
				'learner_default' => in_array( $_POST['learner_default'], $valid_roles, true ) ? $_POST['learner_default'] : 'subscriber',
				'hide_navigation' => (int) $_POST['hide_navigation'],
				'cc_version' => (string) $_POST['cc_version'],
			];
			$result = update_site_option( self::OPTION, $update );
			return $result;
		}
		return false;
	}

	/**
	 * @return array{whitelist: string, prompt_for_authentication: int, book_override: int, admin_default: string, staff_default: string, learner_default: string, hide_navigation: int, cc_version: string}
	 */
	public function getSettings() {

		$options = get_site_option( self::OPTION, [] );

		if ( empty( $options['whitelist'] ) ) {
			$options['whitelist'] = '';
		}
		if ( ! isset( $options['prompt_for_authentication'] ) ) {
			$options['prompt_for_authentication'] = 0;
		}
		if ( ! isset( $options['book_override'] ) ) {
			$options['book_override'] = 1;
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
		if ( ! isset( $options['hide_navigation'] ) ) {
			$options['hide_navigation'] = 0;
		}
		if ( ! isset( $options['cc_version'] ) ) {
			$options['cc_version'] = '1.3';
		}

		return $options;
	}

	/**
	 * Add LTI Settings menu (Book)
	 */
	public function addBookSettingsMenu() {
		if ( $this->getSettings()['book_override'] ) {
			$parent_slug = \Pressbooks\Admin\Dashboard\init_network_integrations_menu();
			add_submenu_page(
				$parent_slug,
				__( 'LTI Settings', 'pressbooks-lti-provider' ),
				__( 'LTI Settings', 'pressbooks-lti-provider' ),
				'manage_network',
				'pb_lti_settings',
				[ $this, 'printBookSettingsMenu' ]
			);
		}
	}

	/**
	 * Print LTI Settings Form (Book)
	 */
	public function printBookSettingsMenu() {
		if ( $this->saveBookSettings() ) {
			echo '<div id="message" role="status" class="updated notice is-dismissible"><p>' . __( 'Settings saved.' ) . '</p></div>';
		}
		$html = blade()->render(
			'book.settings', [
				'form_url' => admin_url( '/admin.php?page=pb_lti_settings' ),
				'options' => $this->getBookSettings(),
			]
		);
		echo $html;
	}

	/**
	 * @return bool
	 */
	public function saveBookSettings() {
		if ( ! empty( $_POST ) && check_admin_referer( 'pb-lti-provider-book' ) ) {
			$valid_roles = [ 'administrator', 'editor', 'author', 'contributor', 'subscriber', 'anonymous' ];
			$update = [
				'admin_default' => in_array( $_POST['admin_default'], $valid_roles, true ) ? $_POST['admin_default'] : 'subscriber',
				'staff_default' => in_array( $_POST['staff_default'], $valid_roles, true ) ? $_POST['staff_default'] : 'subscriber',
				'learner_default' => in_array( $_POST['learner_default'], $valid_roles, true ) ? $_POST['learner_default'] : 'subscriber',
				'hide_navigation' => (int) $_POST['hide_navigation'],
				'cc_version' => (string) $_POST['cc_version'],
			];
			$result = update_option( self::OPTION, $update );
			return $result;
		}
		return false;
	}

	/**
	 * @return array{admin_default: string, staff_default: string, learner_default: string, hide_navigation: int, cc_version: string}
	 */
	public function getBookSettings() {
		if ( $this->getSettings()['book_override'] ) {
			$options = get_option( self::OPTION, [] );
		} else {
			$options = [];
		}
		$defaults = $this->getSettings();

		if ( empty( $options['admin_default'] ) ) {
			$options['admin_default'] = $defaults['admin_default'];
		}
		if ( empty( $options['staff_default'] ) ) {
			$options['staff_default'] = $defaults['staff_default'];
		}
		if ( empty( $options['learner_default'] ) ) {
			$options['learner_default'] = $defaults['learner_default'];
		}
		if ( ! isset( $options['hide_navigation'] ) ) {
			$options['hide_navigation'] = $defaults['hide_navigation'];
		}
		if ( ! isset( $options['cc_version'] ) ) {
			$options['cc_version'] = $defaults['cc_version'];
		}

		return $options;
	}

}
