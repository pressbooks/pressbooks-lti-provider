<?php

namespace PressbooksLtiProvider;

use IMSGlobal\LTI\Profile;
use IMSGlobal\LTI\ToolProvider;
use Pressbooks\Book;

class Tool extends ToolProvider\ToolProvider {

	const META_KEY = 'pressbooks_lti_identity';

	/**
	 * Maximum permitted length of parameter value
	 */
	const MAX_LENGTH = 50;

	/**
	 * Options key for storing course and resource ID
	 */
	const CONSUMER_CONTEXT_KEY = 'pressbooks_lti_consumer_context';

	/**
	 * @var Admin
	 */
	protected $admin;

	/**
	 * @var string
	 */
	protected $action;

	/**
	 * @var array
	 */
	protected $params;

	// ------------------------------------------------------------------------
	// Overrides
	// ------------------------------------------------------------------------

	/**
	 * Tool constructor.
	 * Launched by do_format()
	 *
	 * @see \PressbooksLtiProvider\do_format
	 *
	 * @param \IMSGlobal\LTI\ToolProvider\DataConnector\DataConnector $data_connector
	 */
	public function __construct( $data_connector ) {
		parent::__construct( $data_connector );

		$this->debugMode = WP_DEBUG;

		$this->baseUrl = trailingslashit( home_url() );

		// Vendor details
		$this->vendor = new Profile\Item(
			'pressbooks',
			'Pressbooks',
			__( 'Powered by Pressbooks', 'pressbooks-lti-provider' ),
			'https://pressbooks.education/'
		);

		// Product details
		$plugin_info = get_plugin_data( __DIR__ . '/../pressbooks-lti-provider.php', false, false );
		$this->product = new Profile\Item(
			globally_unique_identifier( true ),
			$plugin_info['Name'],
			$plugin_info['Description'],
			$plugin_info['AuthorURI'],
			$plugin_info['Version']
		);

		// Resource handlers for Tool Provider. One $resourceHandlers[] per book. URLs must be relative.
		$launch_url = 'format/lti';
		$icon_url = $this->relativeBookIconUrl();
		$ask_for = [
			'User.id',
			'User.username',
			'Person.email.primary',
			'Membership.role',
		];

		$metadata = Book::getBookInformation();
		$course_name = $metadata['pb_title'] ?? $plugin_info['Name'];
		$course_description = $metadata['pb_about_50'] ?? $metadata['pb_about_140'] ?? null;

		$required_messages = [
			new Profile\Message( 'basic-lti-launch-request', $launch_url, $ask_for ),
		];
		$optional_messages = [
			new Profile\Message( 'ContentItemSelectionRequest', $launch_url, $ask_for ),
		];
		$this->resourceHandlers[] = new Profile\ResourceHandler(
			new Profile\Item( globally_unique_identifier(), $course_name, $course_description ),
			$icon_url,
			$required_messages,
			$optional_messages
		);

		// Services required by Tool Provider
		$this->requiredServices[] = new Profile\ServiceDefinition( [ 'application/vnd.ims.lti.v2.toolproxy+json' ], [ 'POST' ] );
	}

	/**
	 * Process a valid launch request
	 *
	 * Insert code here to handle incoming launches - use the user, context
	 * and resourceLink properties to access the current user, context and resource link.
	 */
	protected function onLaunch() {
		$params = $this->getParams();

		if ( isset( $params['method'] ) && 'createbook' === $params['method'] ) {
			$this->initSessionVars();
			$this->setupUser( $this->user, $this->consumer->consumerGuid );
		} elseif ( $this->getAction() === 'launch' ) {
			$this->initSessionVars();
			$this->setupUser( $this->user, $this->consumer->consumerGuid );
			$this->setupDeepLink();
		} else {
			$this->ok = false;
			$this->message = __( 'Invalid launch URL', 'pressbooks-lti-provider' );
			$this->onError();
		}
	}

	/**
	 * Process a valid content-item request
	 *
	 * Insert code here to handle incoming content-item requests - use the user and context
	 * properties to access the current user and context.
	 */
	protected function onContentItem() {
		// Content Items (more than one LtiLinkItem)
		$this->ok = in_array( 'application/vnd.ims.lti.v1.contentitems+json', $this->mediaTypes, true );
		if ( $this->ok ) {
			// TODO: This specification doesn't seem widely supported?
			// https://www.imsglobal.org/lti/model/mediatype/application/vnd/ims/lti/v1/contentitems%2Bjson/index.html
		}

		// Content Item (a single LtiLinkItem)
		$this->ok = in_array( ToolProvider\ContentItem::LTI_LINK_MEDIA_TYPE, $this->mediaTypes, true ) || in_array( '*/*', $this->mediaTypes, true );
		if ( ! $this->ok ) {
			$this->reason = __( 'Return of an LTI link not offered', 'pressbooks-lti-provider' );
		} else {
			$this->ok = ! in_array( 'none', $this->documentTargets, true ) || ( count( $this->documentTargets ) > 1 );
			if ( ! $this->ok ) {
				$this->reason = __( 'No visible document target offered', 'pressbooks-lti-provider' );
			}
		}
		if ( $this->ok ) {
			$this->initSessionVars();
			$_SESSION['pb_lti_data'] = $_POST['data'] ?? null;
			$url = $this->baseUrl . 'format/lti/contentItemSubmit';
			$this->output = $this->renderContentItemForm( $url );
			return;
		}

		// Error
		$this->onError();
	}

	/**
	 * Process a valid tool proxy registration request
	 *
	 * Insert code here to handle incoming registration requests - use the user
	 * property to access the current user.
	 */
	protected function onRegister() {
		// Sanity check
		if ( empty( $this->consumer ) ) {
			$this->ok = false;
			$this->message = __( 'Invalid tool consumer.', 'pressbooks-lti-provider' );
			return;
		}
		if ( empty( $this->returnUrl ) ) {
			$this->ok = false;
			$this->message = __( 'Return URL was not set.', 'pressbooks-lti-provider' );
			return;
		}
		if ( ! $this->doToolProxyService() ) {
			$this->ok = false;
			$this->message = __( 'Could not establish proxy with consumer.', 'pressbooks-lti-provider' );
			return;
		}

		$success_args = [
			'lti_msg' => __( 'The tool has been successfully registered.', 'pressbooks-lti-provider' ),
			'tool_proxy_guid' => $this->consumer->getKey(),
			'status' => 'success',
		];
		$success_url = esc_url( add_query_arg( $success_args, $this->returnUrl ) );

		$cancel_args = [
			'lti_msg' => __( 'The tool registration has been cancelled.', 'pressbooks-lti-provider' ),
			'status' => 'failure',
		];
		$cancel_url = esc_url( add_query_arg( $cancel_args, $this->returnUrl ) );

		$this->output = $this->renderRegisterForm( $success_url, $cancel_url );
	}

	/**
	 * Process a response to an invalid request
	 *
	 * Insert code here to handle errors on incoming connections - do not expect
	 * the user, context and resourceLink properties to be populated but check the reason
	 * property for the cause of the error.  Return TRUE if the error was fully
	 * handled by this method.
	 */
	protected function onError() {
		$message = $this->message;
		if ( $this->debugMode && ! empty( $this->reason ) ) {
			$message = $this->reason;
		}
		// Display the error message from the provider's side if the consumer has not specified a URL to pass the error to.
		if ( empty( $this->returnUrl ) ) {
			$this->errorOutput = $message;
		}
	}

	// ------------------------------------------------------------------------
	// Overrides, sort of
	// ------------------------------------------------------------------------

	/**
	 * @return string
	 */
	public function getRedirectUrl() {
		return $this->redirectUrl;
	}

	/**
	 * @param Admin $admin
	 */
	public function setAdmin( Admin $admin ) {
		$this->admin = $admin;
	}

	// ------------------------------------------------------------------------
	// Chunks of (ideally) testable code
	// ------------------------------------------------------------------------

	/**
	 * @return string
	 */
	public function getAction() {
		return $this->action;
	}

	/**
	 * @param string $action
	 */
	public function setAction( $action ) {
		$this->action = $action;
	}

	/**
	 * @return array
	 */
	public function getParams() {
		return $this->params;
	}

	/**
	 * @param array $params
	 */
	public function setParams( $params ) {
		$this->params = $params;
	}

	/**
	 * @return string
	 */
	public function relativeBookIconUrl() {
		$icon_url = wp_parse_url( plugins_url( 'pressbooks-lti-provider/assets/dist/images/book.png' ), PHP_URL_PATH );
		$icon_url = ltrim( $icon_url, '/' );
		$home_url = wp_parse_url( $this->baseUrl, PHP_URL_PATH );
		if ( $home_url ) {
			$home_url = rtrim( $home_url, '/' );
			$icon_url = str_repeat( '../', substr_count( $home_url, '/' ) ) . $icon_url;
		}
		return $icon_url;
	}

	/**
	 * Initialize $_SESSION variables
	 */
	public function initSessionVars() {
		// Consumer
		$_SESSION['pb_lti_consumer_pk'] = null;
		$_SESSION['pb_lti_consumer_version'] = null;
		if ( is_object( $this->consumer ) ) {
			$_SESSION['pb_lti_consumer_pk'] = $this->consumer->getRecordId();
			$_SESSION['pb_lti_consumer_version'] = $this->consumer->ltiVersion;
		}

		// Resource
		$_SESSION['pb_lti_resource_pk'] = null;
		if ( is_object( $this->resourceLink ) ) {
			$_SESSION['pb_lti_resource_pk'] = $this->resourceLink->getRecordId();
		}

		// User
		$_SESSION['pb_lti_user_pk'] = null;
		$_SESSION['pb_lti_user_resource_pk'] = null;
		$_SESSION['pb_lti_user_consumer_pk'] = null;
		if ( is_object( $this->user ) ) {
			$_SESSION['pb_lti_user_pk'] = $this->user->getRecordId();
			if ( is_object( $this->user->getResourceLink() ) ) {
				$_SESSION['pb_lti_user_resource_pk'] = $this->user->getResourceLink()->getRecordId();
				if ( is_object( $this->user->getResourceLink()->getConsumer() ) ) {
					$_SESSION['pb_lti_user_consumer_pk'] = $this->user->getResourceLink()->getConsumer()->getRecordId();
				}
			}
		}

		// Return URL
		$_SESSION['pb_lti_return_url'] = $this->returnUrl;
	}

	/**
	 * @param \IMSGlobal\LTI\ToolProvider\User $user
	 * @param string $guid
	 *
	 * @throws \LogicException
	 */
	public function setupUser( $user, $guid ) {
		if ( ! is_object( $this->admin ) ) {
			throw new \LogicException( '$this->admin is not an object. It must be set before calling setupUser()' );
		}

		// Always logout before running the rest of this procedure
		wp_logout();

		// Role
		$settings = $this->admin->getBookSettings();
		if ( $user->isAdmin() ) {
			$role = $settings['admin_default'];
		} elseif ( $user->isStaff() ) {
			$role = $settings['staff_default'];
		} elseif ( $user->isLearner() ) {
			$role = $settings['learner_default'];
		} else {
			$role = 'anonymous';
		}

		// ID
		$net_id = $user->getId();

		// Email
		$email = trim( $user->email );
		if ( empty( $email ) ) {
			// The LMS did not give us an email address. Make one up based on the ID.
			$email = "{$net_id}@127.0.0.1";
		}

		// An easier to read username, if possible
		$username = strstr( $email, '@', true );

		/**
		 * @since 1.1.1
		 *
		 * @param string $email
		 * @param string $net_id
		 * @param string $plugin_name
		 */
		$email = apply_filters( 'pb_integrations_multidomain_email', $email, $net_id, 'pressbooks-lti-provider' );

		// LTI ID
		$lti_id = "{$guid}|{$net_id}";

		// Prompt for authentication logic
		$lti_id_was_matched = false;
		$is_new_user = false;

		// Try to find a matching WordPress user with LTI ID
		$wp_user = $this->matchUserById( $lti_id );
		if ( $wp_user ) {
			$lti_id_was_matched = true;
		} else {
			// Try to match the LTI User with their email
			$wp_user = get_user_by( 'email', $email );
		}
		// edge case where consumer key (e.g. WP0-ZRTCXX) is used to determine login
		if ( ! $wp_user ) {
			$id_scope = $this->consumer->getKey();
			$id_scope   = intval( substr( $id_scope, 2, 1 ) );
			$user_login = $user->getId( $id_scope );
			$user_login = $this->sanitizeUser( $user_login );
			$user_login = apply_filters( 'pre_user_login', $user_login );
			$wp_user = get_user_by( 'login', $user_login );
		}

		// If there's no match then check if we should create a user (Anonymous Guest = No, Everything Else = Yes)
		if ( ! $wp_user && $role !== 'anonymous' ) {
			try {
				list( $user_id, $username ) = $this->createUser( $username, $email );
				$wp_user = get_userdata( $user_id );
				$wp_user->set_role( $role );
				$is_new_user = true;
			} catch ( \Exception $e ) {
				return; // TODO: What should we do on fail?!
			}
		}

		if ( $wp_user ) {
			if ( $this->admin->getSettings()['prompt_for_authentication'] && $lti_id_was_matched === false && $is_new_user === false ) {
				$this->authenticateUser( $wp_user, $lti_id, $lti_id_was_matched, $role );
			} else {
				$this->loginUser( $wp_user, $lti_id, $lti_id_was_matched, $role );
			}
		}
	}

	/**
	 * Prompt for authentication to confirm matching with existing user on initial LTI launch
	 *
	 * @param \WP_User $wp_user
	 * @param string $lti_id
	 * @param bool $lti_id_was_matched
	 * @param string $role
	 */
	public function authenticateUser( $wp_user, $lti_id, $lti_id_was_matched, $role ) {
		$storage = new Entities\Storage();
		$storage->ltiIdWasMatched = $lti_id_was_matched;
		$storage->params = $this->getParams();
		$storage->user = $wp_user;
		$storage->ltiId = $lti_id;
		$storage->role = $role;
		$storage->lmsName = $this->consumer->name ?? $this->consumer->consumerName ?? 'LTI';
		$_SESSION['pb_lti_prompt_for_authentication'] = $storage;
		auth_redirect();
	}

	/**
	 * If the user does not have rights to the book, and role != Anonymous Guest, then add them to the book with appropriate role
	 *
	 * @param \WP_User $wp_user
	 * @param string $lti_id
	 * @param bool $lti_id_was_matched
	 * @param string $role
	 */
	public function loginUser( $wp_user, $lti_id, $lti_id_was_matched, $role ) {
		if ( $role !== 'anonymous' ) {
			if ( is_user_member_of_blog( $wp_user->ID ) ) {
				// Change role of an existing Pressbooks user to the one provided by the LMS.
				//
				// Our customers don't want this behaviour. The LTI certification suite (probably) does...
				// Commenting out, instead of removing, for future facepalm.
				//
				// $wp_user->set_role( $role ); // @codingStandardsIgnoreLine
			} else {
				add_user_to_blog( get_current_blog_id(), $wp_user->ID, $role );
			}
		}
		if ( ! $lti_id_was_matched ) {
			$this->linkAccount( $wp_user->ID, $lti_id );
		}
		// Login the user
		\Pressbooks\Redirect\programmatic_login( $wp_user->user_login );
	}

	/**
	 * Attempt to match a WordPress user to their LTI identity.
	 *
	 * @param string $lti_id Generally, [tool_consumer_instance_guid + user_id] should be used to identify and authenticate the current user in the LTI tool
	 *
	 * @return false|\WP_User
	 */
	public function matchUserById( $lti_id ) {
		global $wpdb;
		$condition = "{$lti_id}|%";
		$query_result = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value LIKE %s", self::META_KEY, $condition ) );
		// attempt to get a WordPress user with the matched id:
		$user = get_user_by( 'id', $query_result );
		return $user;
	}

	/**
	 * Link a user to their LTI identity
	 *
	 * @param int $user_id
	 * @param string $lti_id Generally, [ tool_consumer_instance_guid + user_id ] should be used to identify and authenticate the current user in the LTI tool
	 */
	public function linkAccount( $user_id, $lti_id ) {
		$condition = "{$lti_id}|" . time();
		add_user_meta( $user_id, self::META_KEY, $condition );
	}

	/**
	 * Create user (redirects if there is an error)
	 *
	 * @param string $username
	 * @param string $email
	 *
	 * @throws \Exception
	 *
	 * @return array [ (int) user_id, (string) sanitized username ]
	 */
	public function createUser( $username, $email ) {
		$i = 1;
		$unique_username = $this->sanitizeUser( $username );
		while ( username_exists( $unique_username ) ) {
			$unique_username = $this->sanitizeUser( "{$username}{$i}" );
			++$i;
		}

		$username = $unique_username;
		$email = sanitize_email( $email );

		// Attempt to generate the user and get the user id
		// we use wp_create_user instead of wp_insert_user so we can handle the error when the user being registered already exists
		$user_id = wp_create_user( $username, wp_generate_password(), $email );

		// Check if the user was actually created:
		if ( is_wp_error( $user_id ) ) {
			// there was an error during registration, redirect and notify the user:
			throw new \Exception( $user_id->get_error_message() );
		}

		remove_user_from_blog( $user_id, 1 );

		return [ $user_id, $username ];
	}

	/**
	 * Multisite has more restrictions on user login character set
	 *
	 * @see https://core.trac.wordpress.org/ticket/17904
	 *
	 * @param string $username
	 *
	 * @return string
	 */
	public function sanitizeUser( $username ) {
		$unique_username = sanitize_user( $username, true );
		$unique_username = strtolower( $unique_username );
		$unique_username = preg_replace( '/[^a-z0-9]/', '', $unique_username );
		return $unique_username;
	}

	/**
	 * Setup Deep Link
	 */
	public function setupDeepLink() {

		$params = $this->getParams();

		if ( empty( $params[0] ) ) {
			// Format: https://book/format/lti/launch
			$this->redirectUrl = home_url();
		} elseif ( empty( $params[1] ) ) {
			if ( is_numeric( $params[0] ) ) {
				// Format: https://book/format/lti/launch/123
				$url = wp_get_shortlink( $params[0] );
				if ( $url ) {
					$this->redirectUrl = $url;
				}
			} else {
				// Format: https://book/format/lti/launch/Hello%20World
				// TODO
			}
		} else {
			if ( in_array( $params[0], [ 'front-matter', 'part', 'chapter', 'back-matter' ], true ) ) {
				// Format: https://book/format/lti/launch/front-matter/introduction
				$args = [
					'name' => $params[1],
					'post_type' => $params[0],
					'post_status' => [ 'draft', 'web-only', 'private', 'publish' ],
					'numberposts' => 1,
				];
				$posts = get_posts( $args );
				if ( $posts ) {
					$this->redirectUrl = get_permalink( $posts[0]->ID );
				}
			}
		}

		if ( empty( $this->redirectUrl ) ) {
			$this->ok = false;
			$this->message = __( 'Deep link was not found.', 'pressbooks-lti-provider' );
			$this->onError();
		}
	}

	/**
	 * Output a form to select a single LTI link
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	public function renderContentItemForm( $url ) {
		return blade()->render(
			'PressbooksLtiProvider::lti.selection', [
				'title' => get_bloginfo( 'name' ),
				'url' => $url,
				'book_structure' => Book::getBookStructure(),
			]
		);
	}

	/**
	 * Show a page with options to redirect back to consumer
	 *
	 * @param string $success_url
	 * @param string $cancel_url
	 *
	 * @return string
	 */
	public function renderRegisterForm( $success_url, $cancel_url ) {
		return blade()->render(
			'PressbooksLtiProvider::lti.register', [
				'title' => get_bloginfo( 'name' ),
				'success_url' => $success_url,
				'cancel_url' => $cancel_url,
			]
		);
	}

	/**
	 * Check ToolProxyRegistrationRequest against a whitelist
	 *
	 * @return bool
	 * @throws \LogicException
	 */
	public function validateRegistrationRequest() {
		if ( isset( $_POST['lti_message_type'] ) && $_POST['lti_message_type'] === 'ToolProxyRegistrationRequest' ) {

			if ( ! empty( $_POST['tc_profile_url'] ) ) {
				$url = $_POST['tc_profile_url'];
			} elseif ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
				$url = $_SERVER['HTTP_REFERER'];
			} else {
				return false;
			}

			if ( ! is_object( $this->admin ) ) {
				throw new \LogicException( '$this->admin is not an object. It must be set before calling validateRegistrationRequest()' );
			}

			$domain = wp_parse_url( $url, PHP_URL_HOST );
			$whitelist = $this->admin->getSettings()['whitelist'];
			if ( ! is_array( $whitelist ) ) {
				$whitelist = explode( "\n", $whitelist );
			}

			// Remove empty entries
			$whitelist = array_filter(
				$whitelist,
				function ( $var ) {
					if ( is_string( $var ) ) {
						$var = trim( $var );
					}
					return ! empty( $var );
				}
			);
			if ( empty( $whitelist ) ) {
				return false; // If the whitelist is empty then automatic registrations are disabled.
			}

			$domain = trim( strtolower( $domain ) );
			foreach ( $whitelist as $allowed ) {
				$allowed = trim( strtolower( $allowed ) );
				if ( $domain === $allowed ) {
					return true;
				}
				$dotted_domain = ".$allowed";
				if ( $dotted_domain === substr( $domain, -strlen( $dotted_domain ) ) ) {
					return true;
				}
			}

			return false;
		}

		// This is not even a ToolProxyRegistrationRequest, so yes, it's valid
		return true;
	}

	/**
	 * Process incoming request, check authenticity of the LTI launch request
	 *
	 * @since 1.4.0
	 *
	 * @param array $params
	 */
	public function processRequest( $params ) {
		$this->setParams( $params );
		$this->setParameterConstraint(
			'oauth_consumer_key', true, self::MAX_LENGTH, [
				'basic-lti-launch-request',
				'ContentItemSelectionRequest',
			]
		);
		$this->setParameterConstraint( 'resource_link_id', true, self::MAX_LENGTH, [ 'basic-lti-launch-request' ] );
		$this->setParameterConstraint( 'user_id', true, self::MAX_LENGTH, [ 'basic-lti-launch-request' ] );
		$this->setParameterConstraint( 'roles', true, null, [ 'basic-lti-launch-request' ] );
		if ( ! $this->validateRegistrationRequest() ) {
			$this->ok      = false;
			$this->message = __( 'Unauthorized registration request. Tool Consumer is not in whitelist of allowed domains.', 'pressbooks-lti-provider' );
		}
	}

	/**
	 * Builds a book title from expected values and applies an opinionated format.
	 * Supplies default values should arguments be empty
	 *
	 * @since 1.4.0
	 *
	 * @param $course_name
	 * @param $course_id
	 * @param $activity_name
	 * @param $activity_id
	 *
	 * @return string
	 */
	public function buildTitle( $course_name, $course_id, $activity_name, $activity_id ) {
		$course   = ( ! empty( $course_name ) ? $course_name : 'Course ID ' . $course_id );
		$activity = ( ! empty( $activity_name ) ? $activity_name : 'Activity ID ' . $activity_id );

		$title = sprintf( '%1$s: %2$s', $course, $activity );
		$title = wp_strip_all_tags( $title, false );
		$title = ( strlen( $title ) <= 1 ) ? 'Untitled' : $title;

		return $title;
	}

	/**
	 * Takes an activity name, massages it into compliance for a valid domain
	 *
	 * @param $resource_link_title string Activity name derived from the Tool Consumer
	 *
	 * @return bool|string
	 * @since 1.4.0
	 */
	public function buildAndValidateUrl( $resource_link_title ) {
		global $domain;

		$current_network          = get_network();
		$base                     = $current_network->path;
		$scheme                   = wp_parse_url( network_home_url(), PHP_URL_SCHEME );
		$illegal_names            = get_site_option( 'illegal_names' );
		$minimum_site_name_length = apply_filters( 'minimum_site_name_length', 4 );

		$blog_name = sanitize_title_with_dashes( remove_accents( $resource_link_title ) );
		$blog_name = preg_replace( '/-/', '', $blog_name );

		if ( preg_match( '/^[0-9]*$/', $blog_name ) ) {
			$blog_name .= 'a';
		}

		if ( in_array( $blog_name, $illegal_names, true ) ) {
			$this->ok = false;
			$this->message = __( 'Sorry, the activity name uses a reserved word', 'pressbooks-lti-provider' );
			$this->handleRequest();
			return '';
		}

		if ( strlen( $blog_name ) < $minimum_site_name_length ) {
			$blog_name = str_pad( $blog_name, 4, '1' );
		}

		if ( is_subdomain_install() ) {
			$host = wp_parse_url( esc_url( $domain ), PHP_URL_HOST );
			$host = explode( '.', $host );
			if ( count( $host ) > 2 ) {
				array_shift( $host );
			}
			$bare_domain = implode( '.', $host );
			$my_domain   = $blog_name . '.' . $bare_domain;
			$path        = $base;

		} else {
			$illegal_sub_directory_names = array_merge( $illegal_names, get_subdirectory_reserved_names() );
			if ( in_array( $blog_name, $illegal_sub_directory_names, true ) ) {
				$this->ok = false;
				$this->message = __( 'Sorry, the activity name uses a reserved word', 'pressbooks-lti-provider' );
				$this->handleRequest();
				return '';
			}
			$my_domain = "$domain";
			$path      = $base . $blog_name . '/';

		}

		$path = ( 0 === strcmp( $path, '/' ) ) ? '' : $path;

		return sprintf( '%1$s://%2$s%3$s', $scheme, $my_domain, $path );
	}

	/**
	 * Creates a unique URL based on a given URL
	 *
	 * @param $url
	 *
	 * @return string
	 * @since 1.4.0
	 */
	public function maybeDisambiguateDomain( $url ) {
		$parts = wp_parse_url( $url );
		if ( ! isset( $parts['host'] ) ) {
			return '';
		}

		$domain = $parts['host'];
		$path = $parts['path'];

		if ( is_subdomain_install() ) {
			$i = 1;
			while ( domain_exists( $domain, $parts['path'], 1 ) && $i < 1000 ) {
				$domain = "{$parts['host']}{$i}";
				++ $i;
			}
		} else {
			$i = 1;
			while ( domain_exists( $parts['host'], $path, 1 ) && $i < 1000 ) {
				$path = untrailingslashit( $parts['path'] ) . $i;
				++ $i;
			}
		}

		return sprintf( '%1$s://%2$s%3$s', $parts['scheme'], $domain, untrailingslashit( $path ) );
	}

	/**
	 * Create a new book, launched via LTI
	 *
	 * @param string $new_book_url URL of the new book to be created
	 * @param string $title Title of the book
	 * @param int $user_id ID of the user creating the book
	 * @param int $resource_link_id ID of the activity, derived from the Tool Consumer
	 * @param int $context_id ID of the course the activity belongs to, derived from the Tool Consumer
	 *
	 * @return int|\WP_Error
	 * @since 1.4.0
	 */
	public function createNewBook( $new_book_url, $title, $user_id, $resource_link_id, $context_id ) {
		$url    = untrailingslashit( $new_book_url );
		$domain = wp_parse_url( $url, PHP_URL_HOST );
		$path   = wp_parse_url( $url, PHP_URL_PATH );

		$book_id = wpmu_create_blog( $domain, $path, $title, $user_id );
		add_blog_option(
			$book_id, self::CONSUMER_CONTEXT_KEY, [
				'resource_link_id' => $resource_link_id,
				'context_id'       => $context_id,
			]
		);

		return $book_id;
	}

	/**
	 * Checks for both an existing domain and expected values in the options table
	 *
	 * @param $url string URL of the book to check
	 * @param $resource_link_id string ID of the activity, derived from the Tool Consumer
	 * @param $context_id string ID of the course the activity belongs to, derived from the Tool Consumer
	 *
	 * @return bool
	 * @since 1.4.0
	 */
	public function validateLtiBookExists( $url, $resource_link_id, $context_id ) {
		$parts = domain_and_path( $url );
		if ( ! $parts ) {
			return false;
		}
		$exists = ( domain_exists( $parts[0], $parts[1] ) ) ? true : false;

		if ( $exists ) {
			$book_id = get_blog_id_from_url( $parts[0], $parts[1] );
			$options = get_blog_option( $book_id, self::CONSUMER_CONTEXT_KEY );
			// Check if the book has been created already by the same activity in the same course.
			if ( $options ) {
				$same_activity = 0 === strcmp( $options['resource_link_id'], $resource_link_id );
				$same_course   = 0 === strcmp( $options['context_id'], $context_id );
				$exists        = true === $same_activity && true === $same_course;
			} else {
				update_blog_option(
					$book_id, self::CONSUMER_CONTEXT_KEY, [
						'resource_link_id' => $resource_link_id,
						'context_id'       => $context_id,
					]
				);
			}
		}

		return $exists;
	}
}
