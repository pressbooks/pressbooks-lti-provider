<?php

use Pressbooks\Lti\Provider\Database;

class DatabaseTest extends \WP_UnitTestCase {

	public function _alter_temporary_tables( $query ) {
		if ( 'ALTER TABLE' === substr( trim( $query ), 0, 11 ) && strpos( $query, 'ADD CONSTRAINT' ) !== false && strpos( $query, 'FOREIGN KEY' ) !== false ) {
			return 'SELECT 1'; // Replace foreign key query with a fake query
		}
		return $query;
	}

	public function test_getConnector() {
		$data_connector1 = Database::getConnector();
		$this->assertInstanceOf( '\IMSGlobal\LTI\ToolProvider\DataConnector\DataConnector', $data_connector1 );
		$data_connector2 = Database::getConnector();
		$this->assertTrue( $data_connector1 === $data_connector2 ); // reuse connections, objects are identical if and only if they refer to the same instance of the same class
	}

	public function test_tablePrefix() {
		$prefix = Database::tablePrefix();
		$this->assertStringEndsWith( '_pressbooks_', $prefix );
	}

	public function test_installTables() {

		// Cannot make a foreign key changes on temporary tables. (errno: 150 "Foreign key constraint is incorrectly formed")
		// @see \WP_UnitTestCase::_create_temporary_tables
		add_filter( 'query', [ $this, '_alter_temporary_tables' ] );

		$option = \Pressbooks\Lti\Provider\Database::OPTION;
		$this->assertTrue( get_site_option( $option ) === false );
		Database::installTables();
		$this->assertTrue( get_site_option( $option ) > 0 );
	}
}