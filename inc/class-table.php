<?php

namespace Pressbooks\Lti\Provider;

use IMSGlobal\LTI\ToolProvider\ToolConsumer;

class Table extends \WP_List_Table {

	/**
	 * @var \IMSGlobal\LTI\ToolProvider\DataConnector\DataConnector
	 */
	protected $connector;

	/**
	 * @var Tool
	 */
	protected $tool;

	function __construct() {
		$args = [
			'singular' => 'consumer',
			'plural' => 'consumers', // Parent will create bulk nonce: "bulk-{$plural}"
			'ajax' => true,
		];
		parent::__construct( $args );

		$this->connector = Database::getConnector();
		$this->tool = new Tool( $this->connector );
	}

	/**
	 * This method is called when the parent class can't find a method
	 * for a given column. For example, if the class needs to process a column
	 * named 'title', it would first see if a method named $this->column_title()
	 * exists. If it doesn't this one will be used.
	 *
	 * @see WP_List_Table::single_row_columns()
	 *
	 * @param object $item A singular item (one full row's worth of data)
	 * @param string $column_name The name/slug of the column to be processed
	 *
	 * @return string Text or HTML to be placed inside the column <td>
	 */
	function column_default( $item, $column_name ) {
		return esc_html( $item[ $column_name ] );
	}

	/**
	 * @param array $item A singular item (one full row's worth of data)
	 *
	 * @return string Text to be placed inside the column <td>
	 */
	function column_name( $item ) {
		$edit_url = sprintf( '/admin.php?page=%s&action=%s&ID=%s', $_REQUEST['page'], 'edit', $item['ID'] );
		$edit_url = network_admin_url( $edit_url );
		$actions['edit'] = sprintf(
			'<a href="%s" aria-label="%s">%s</a>',
			$edit_url,
			/* translators: %s: post title */
			esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;' ), $item['name'] ) ),
			__( 'Edit' )
		);

		$delete_url = sprintf( '/admin.php?page=%s&action=%s&ID=%s', $_REQUEST['page'], 'delete', $item['ID'] );
		$delete_url = network_admin_url( $delete_url );
		$delete_url = esc_url( add_query_arg( '_wpnonce', wp_create_nonce( 'bulk-consumers' ), $delete_url ) );
		$onclick = 'onclick="if ( !confirm(\'' . esc_attr( __( 'Are you sure you want to delete this?', 'pressbooks' ) ) . '\') ) { return false }"';
		$actions['trash'] = sprintf(
			'<a href="%s" class="submitdelete" aria-label="%s" ' . $onclick . '>%s</a>',
			$delete_url,
			/* translators: %s: post title */
			esc_attr( sprintf( __( 'Move &#8220;%s&#8221; to the Trash' ), $item['name'] ) ),
			_x( 'Trash', 'verb' )
		);

		return sprintf(
			'<div class="row-title"><a href="%1$s" class="title">%2$s</a></div> %3$s',
			$edit_url,
			$item['name'],
			$this->row_actions( $actions )
		);
	}

	/**
	 * @param array $item A singular item (one full row's worth of data)
	 *
	 * @return string Text to be placed inside the column <td>
	 */
	function column_available( $item ) {
		if ( ! empty( $item['available'] ) ) {
			return '✅';
		} else {
			return '❌';
		}
	}

	/**
	 * @param array $item A singular item (one full row's worth of data)
	 *
	 * @return string Text to be placed inside the column <td>
	 */
	function column_protected( $item ) {
		if ( ! empty( $item['protected'] ) ) {
			return '✅';
		} else {
			return '❌';
		}
	}

	/**
	 * @param array $item
	 *
	 * @return string
	 */
	function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name=ID[]" value="%s" />', $item['ID'] );
	}

	/**
	 * @return array
	 */
	function get_columns() {
		return [
			'cb' => '<input type="checkbox" />',
			'name' => __( 'Name', 'pressbooks-lti-provider' ),
			'base_url' => __( 'Base URL', 'pressbooks-lti-provider' ),
			'key' => __( 'Key', 'pressbooks-lti-provider' ),
			'version' => __( 'Version', 'pressbooks-lti-provider' ),
			'last_access' => __( 'Last access', 'pressbooks-lti-provider' ),
			'available' => __( 'Available', 'pressbooks-lti-provider' ),
			'protected' => __( 'Protected', 'pressbooks-lti-provider' ),
		];
	}

	/**
	 * @return array
	 */
	function get_sortable_columns() {
		return [];
	}

	/**
	 * @return array
	 */
	function get_bulk_actions() {
		return [
			'delete' => 'Delete',
		];
	}

	/**
	 * @param ToolConsumer $consumer
	 *
	 * @return string
	 */
	protected function determineBaseUrl( $consumer ) {
		if ( empty( $consumer->toolProxy ) ) {
			return '';
		}

		if ( is_object( $consumer->toolProxy ) ) {
			$tool_proxy = $consumer->toolProxy;
		} elseif ( is_json( $consumer->toolProxy ) ) {
			$tool_proxy = json_decode( $consumer->toolProxy );
		} else {
			$tool_proxy = null;
		}
		if ( ! is_object( $tool_proxy ) ) {
			return '';
		}

		if ( isset(
			$tool_proxy->tool_profile,
			$tool_proxy->tool_profile->base_url_choice,
			$tool_proxy->tool_profile->base_url_choice[0],
			$tool_proxy->tool_profile->base_url_choice[0]->default_base_url
		) ) {
			return $tool_proxy->tool_profile->base_url_choice[0]->default_base_url;
		}

		return '';
	}

	/**
	 * Prepares the list of items for displaying.
	 */
	function prepare_items() {

		// Process any actions first
		$this->processBulkActions();

		// Load up our consumers
		$consumers = $this->tool->getConsumers();

		// Define Columns
		$columns = $this->get_columns();
		$hidden = [];
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = [ $columns, $hidden, $sortable ];

		// Pagination
		$per_page = 1000;
		$current_page = $this->get_pagenum();
		$total_items = count( $consumers );

		$data = [];
		foreach ( $consumers as $consumer ) {
			/** @var ToolConsumer $consumer */
			$base_url = $this->determineBaseUrl( $consumer );
			$data[] = [
				'ID' => $consumer->getRecordId(),
				'base_url' => $base_url,
				'name' => $consumer->name,
				'key' => $consumer->getKey(),
				'version' => $consumer->consumerVersion,
				'last_access' => ! empty( $consumer->lastAccess ) ? date( 'Y-m-d', $consumer->lastAccess ) : __( 'Never', 'pressbooks-lti-provider' ),
				'available' => $consumer->getIsAvailable(),
				'protected' => $consumer->protected,
			];
		}

		/* The WP_List_Table class does not handle pagination for us, so we need
		 * to ensure that the data is trimmed to only the current page. We can use
		 * array_slice() to
		 */
		$data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );

		$this->items = $data;

		$this->set_pagination_args(
			[
				'total_items' => $total_items,
				'per_page' => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			]
		);
	}

	/**
	 * Delete
	 */
	protected function processBulkActions() {
		if ( 'delete' === $this->current_action() ) {
			check_admin_referer( 'bulk-consumers' );
			$ids = isset( $_REQUEST['ID'] ) ? $_REQUEST['ID'] : [];
			if ( ! is_array( $ids ) ) {
				$ids = [ $ids ];
			}
			if ( ! empty( $ids ) ) {
				foreach ( $ids as $id ) {
					$consumer = ToolConsumer::fromRecordId( $id, $this->connector );
					$consumer->delete();
				}
			}
		}
	}
}
