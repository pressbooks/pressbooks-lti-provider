<?php

namespace Pressbooks\Lti\Provider;

use IMSGlobal\LTI\Profile;
use IMSGlobal\LTI\ToolProvider;
use Pressbooks\Book;

class Tool extends ToolProvider\ToolProvider {

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
	 * @see \Pressbooks\Lti\Provider\do_format
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
			globally_unique_identifier(),
			$plugin_info['Name'],
			$plugin_info['Description'],
			$plugin_info['AuthorURI'],
			$plugin_info['Version']
		);

		// Resource handlers for Tool Provider

		$launch_url = 'format/lti';
		$icon_url = null;
		$ask_for = [
			'User.id',
			'User.username',
			'Person.email.primary',
			'Membership.role',
		];

		$required_messages = [
			new Profile\Message( 'basic-lti-launch-request', $launch_url, $ask_for ),
		];
		$optional_messages = [
			new Profile\Message( 'ContentItemSelectionRequest', $launch_url, $ask_for ),
		];
		$this->resourceHandlers[] = new Profile\ResourceHandler(
			new Profile\Item( globally_unique_identifier(), $plugin_info['Name'], $plugin_info['Description'] ),
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
		if ( $this->getAction() === 'launch' ) {
			$this->initSessionVars();
			$this->setupUser( $this->user );
			$this->setupDeepLink();
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
			'lti_msg' => __( 'Successful registration', 'pressbooks-lti-provider' ),
			'tool_proxy_guid' => $this->consumer->getKey(),
			'status' => 'success',
		];
		$success_url = esc_url( add_query_arg( $success_args, $this->returnUrl ) );

		$cancel_args = [
			'lti_msg' => __( 'The tool registration has been cancelled', 'pressbooks-lti-provider' ),
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
	// Chunks of (ideally) testable code
	// ------------------------------------------------------------------------

	/**
	 * @param Admin $admin
	 */
	public function setAdmin( Admin $admin ) {
		$this->admin = $admin;
	}

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
	 */
	public function setupUser( $user ) {
		$wp_user = get_user_by( 'email', $user->email );
		if ( ! $wp_user ) {
			if ( $user->isAdmin() ) {
				// TODO: Create editor
			} elseif ( $user->isStaff() ) {
				// TODO: Create reviewer
			} elseif ( $user->isLearner() ) {
				// TODO: Create subscriber
			}
		}
		if ( $wp_user ) {
			$this->programmaticLogin( $wp_user->user_login );
		}
	}

	/**
	 * Programmatically logs a user in
	 *
	 * @param string $username
	 *
	 * @return bool True if the login was successful; false if it wasn't
	 */
	public function programmaticLogin( $username ) {
		if ( is_user_logged_in() ) {
			wp_logout();
		}

		$credentials = [
			'user_login' => $username,
		];

		add_filter( 'authenticate', [ $this, 'allowProgrammaticLogin' ], 10, 3 ); // hook in earlier than other callbacks to short-circuit them
		$user = wp_signon( $credentials );
		remove_filter( 'authenticate', [ $this, 'allowProgrammaticLogin' ], 10 );

		if ( is_a( $user, 'WP_User' ) ) {
			wp_set_current_user( $user->ID, $user->user_login );
			if ( is_user_logged_in() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * An 'authenticate' filter callback that authenticates the user using only the username.
	 *
	 * To avoid potential security vulnerabilities, this should only be used in the context of a programmatic login,
	 * and unhooked immediately after it fires.
	 *
	 * @param \WP_User $user
	 * @param string $username
	 * @param string $password
	 *
	 * @return bool|\WP_User a WP_User object if the username matched an existing user, or false if it didn't
	 */
	public function allowProgrammaticLogin( $user, $username, $password ) {
		return get_user_by( 'login', $username );
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
			$this->reason = __( 'Deep link was not found.', 'pressbooks-lti-provider' );
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
		$html = blade()->render(
			'selection', [
				'title' => get_bloginfo( 'name' ),
				'url' => $url,
				'book_structure' => Book::getBookStructure(),
			]
		);

		return $html;
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
		$html = blade()->render(
			'register', [
				'title' => get_bloginfo( 'name' ),
				'success_url' => $success_url,
				'cancel_url' => $cancel_url,
			]
		);

		return $html;
	}

	/**
	 * Check ToolProxyRegistrationRequest against a whitelist
	 *
	 * @return bool
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
				throw new \LogicException( '$this->admin is not an object. It must be set before calling validateRegistrationRequest' );
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
				return true;
			}

			$whitelist = array_map( 'strtolower', $whitelist );
			$domain = strtolower( $domain );
			foreach ( $whitelist as $allowed ) {
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

}
