<?php

/**
 * @group table
 */


class TableTest extends \WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		$GLOBALS['hook_suffix'] = '';
		$this->table = new \PressbooksLtiProvider\Table();
	}

	/**
	 * @return \PressbooksLtiProvider\Admin
	 */
	protected function getMockAdmin() {

		$stub1 = $this
			->getMockBuilder( '\PressbooksLtiProvider\Admin' )
			->getMock();
		$stub1
			->method( 'getSettings' )
			->willReturn(
				[
					'whitelist' => "pressbooks.test\r\nnpressbooks.education\r\n",
					'prompt_for_authentication' => 0,
					'book_override' => 1,
					'admin_default' => 'subscriber',
					'staff_default' => 'subscriber',
					'learner_default' => 'subscriber',
					'hide_navigation' => 0,
				]
			);
		$stub1
			->method( 'getBookSettings' )
			->willReturn(
				[
					'admin_default' => 'subscriber',
					'staff_default' => 'subscriber',
					'learner_default' => 'subscriber',
					'hide_navigation' => 0,
				]
			);

		return $stub1;
	}

	public function test_columnName() {
		$_REQUEST['page'] = 1;
		$item = [
			'ID' => 1,
			'name' => 'Test',
		];
		$string_table = $this->table->column_name( $item );
		$str_match_1 = "<div class=\"row-title\"><a href=\"http://example.org/wp-admin/network/admin.php?page=1&action=edit&ID=1\" class=\"title\">Test</a></div> <div class=\"row-actions\"><span class='edit'><a href=\"http://example.org/wp-admin/network/admin.php?page=1&action=edit&ID=1\" aria-label=\"Edit &#8220;Test&#8221;\">Edit</a> | </span><span class='trash'><a href=\"http://example.org/wp-admin/network/admin.php?page=1&#038;action=delete&#038;ID=1&#038";
		$str_match_2 = " class=\"submitdelete\" aria-label=\"Move &#8220;Test&#8221; to the Trash\" onclick=\"if ( !confirm('Are you sure you want to delete this?') ) { return false }\">Trash</a></span></div><button type=\"button\" class=\"toggle-row\"><span class=\"screen-reader-text\">Show more details</span></button>";
		$this->assertStringContainsString( $str_match_1, $string_table );
		$this->assertStringContainsString( $str_match_2, $string_table );
	}

	public function test_columnAvailable() {
		$item_available = [ 'available' => true ];
		$item_unavailable = [ 'available' => '' ];
		$this->assertEquals( '✅', $this->table->column_available( $item_available ) );
		$this->assertEquals( '❌', $this->table->column_available( $item_unavailable ) );
	}

	public function test_protected() {
		$item_available = [ 'protected' => true ];
		$item_unavailable = [ 'protected' => '' ];
		$this->assertEquals( '✅', $this->table->column_protected( $item_available ) );
		$this->assertEquals( '❌', $this->table->column_protected( $item_unavailable ) );
	}

	public function test_determineBaseUrl() {
		$tool_consumer = new IMSGlobal\LTI\ToolProvider\ToolConsumer();
		$connector = PressbooksLtiProvider\Database::getConnector();
		$tool_consumer->toolProxy = new IMSGlobal\LTI\ToolProvider\ToolProxy( $connector, null );
		$import_class = new \ReflectionClass( 'PressbooksLtiProvider\Table' );
		$determine_base_url = $import_class->getMethod( 'determineBaseUrl' );
		$determine_base_url->setAccessible( true );
		$url = $determine_base_url->invokeArgs( $this->table, [ $tool_consumer ] );
		$this->assertEquals( '', $url );
	}
}
