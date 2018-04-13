<?php

namespace Pressbooks\Lti\Provider;

class Table extends \WP_List_Table {

	protected $tool;

	function __construct() {
		$args = [
			'singular' => 'consumer',
			'plural' => 'consumers', // Parent will create bulk nonce: "bulk-{$plural}"
			'ajax' => true,
		];
		parent::__construct( $args );

		$connector = Database::getConnector();
		$this->tool = new Tool( $connector );
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

	function prepare_items() {

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
			/** @var \IMSGlobal\LTI\ToolProvider\ToolConsumer $consumer */
			$data[] = [
				'ID' => $consumer->getRecordId(),
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
}
