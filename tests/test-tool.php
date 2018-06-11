<?php

class ToolTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var \Pressbooks\Lti\Provider\Tool
	 */
	protected $tool;

	/**
	 * @return \Pressbooks\Lti\Provider\Admin
	 */
	protected function getMockAdmin() {

		$stub1 = $this
			->getMockBuilder( '\Pressbooks\Lti\Provider\Admin' )
			->getMock();
		$stub1
			->method( 'getSettings' )
			->willReturn(
				[
					'whitelist' => "pressbooks.test\r\nnpressbooks.education\r\n",
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

	/**
	 *
	 */
	public function setUp() {
		parent::setUp();
		$connector = Pressbooks\Lti\Provider\Database::getConnector();
		$tool = new Pressbooks\Lti\Provider\Tool( $connector );
		$tool->setAdmin( $this->getMockAdmin() );
		$this->tool = $tool;
	}

	public function test_getters_and_setters() {
		$this->tool->setAction( 'a' );
		$this->assertEquals( 'a', $this->tool->getAction() );
		$this->tool->setParams( [ 'b' ] );
		$this->assertEquals( [ 'b' ], $this->tool->getParams() );
	}

	public function test_relativeBookIconUrl() {
		$url = $this->tool->relativeBookIconUrl();
		$this->assertStringEndsWith( 'book.png', $url );
	}

	public function test_initSessionVars() {
		$this->tool->initSessionVars();
		$this->assertTrue( array_key_exists( 'pb_lti_return_url', $_SESSION ) );
	}

	public function test_setupUser() {
		$this->_book();
		$prefix = uniqid( 'test' );
		$email = "{$prefix}@pressbooks.test";

		$user = new \IMSGlobal\LTI\ToolProvider\User();
		$user->ltiUserId = 1;
		$user->setEmail( $email );
		$user->roles = [ 'urn:lti:role:ims/lis/Administrator' ];

		$this->tool->setupUser( $user );
		$user = get_user_by( 'email', $email );
		$this->assertInstanceOf( '\WP_User', $user );
		$this->assertTrue( is_user_member_of_blog( $user->ID ) );
	}

	public function test_setupDeepLink() {
		$this->_book();
		$this->tool->setParams( [ 'back-matter', 'appendix' ] );
		$this->tool->setupDeepLink();
		$this->assertContains( 'http', $this->tool->getRedirectUrl() );
	}

	public function test_renderContentItemForm() {
		$buffer = $this->tool->renderContentItemForm( 'https://pressbooks.test' );
		$this->assertContains( '</form>', $buffer );
	}

	public function test_renderRegisterForm() {
		$buffer = $this->tool->renderRegisterForm( 'https://pressbooks.test/yes', 'https://pressbooks.test/no' );
		$this->assertContains( '</html>', $buffer );
	}

	public function test_validateRegistrationRequest() {
		$this->assertTrue( $this->tool->validateRegistrationRequest() );

		$_POST['lti_message_type'] = 'ToolProxyRegistrationRequest';
		$this->assertFalse( $this->tool->validateRegistrationRequest() );

		$_POST['tc_profile_url'] = 'https://pressbooks.test/path/to/something';
		$this->assertTrue( $this->tool->validateRegistrationRequest() );

		$_POST['tc_profile_url'] = 'https://hotmail.com/path/to/something';
		$this->assertFalse( $this->tool->validateRegistrationRequest() );
	}

}