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

	function column_default( $item, $column_name ) {
		return esc_html( $item[ $column_name ] );
	}

	function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name=ID[]" value="%s" />', $item['ID'] );
	}

	function get_columns() {
		return [
			'cb' => '<input type="checkbox" />',
			'name' => __( 'Name', 'pressbooks-lti-provider' ),
			'base_url' => __( 'Base URL', 'pressbooks-lti-provider' ),
			'key' => __( 'Key', 'pressbooks-lti-provider' ),
			'version' => __( 'Version', 'pressbooks-lti-provider' ),
			'available' => __( 'Available', 'pressbooks-lti-provider' ),
			'protected' => __( 'Protected', 'pressbooks-lti-provider' ),
			'last_access' => __( 'Last access', 'pressbooks-lti-provider' ),
		];
	}

	function get_sortable_columns() {
		return [];
	}

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
				'available' => $consumer->getIsAvailable(),
				'protected' => $consumer->protected,
				'last_access' => ! empty( $consumer->lastAccess ) ? date( 'j-M-Y', $consumer->lastAccess ) : 'Never',
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

	protected function processBulkActions() {
		if ( 'delete' === $this->current_action() ) {
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
