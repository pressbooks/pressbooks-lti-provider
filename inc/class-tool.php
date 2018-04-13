<?php

namespace Pressbooks\Lti\Provider;

use IMSGlobal\LTI\Profile;
use IMSGlobal\LTI\ToolProvider;

class Tool extends ToolProvider\ToolProvider {

	/**
	 * @var string
	 */
	protected $action;

	/**
	 * @var array
	 */
	protected $params;

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

		$this->baseUrl  = trailingslashit( home_url() );

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

		$required_messages = [
			new Profile\Message( 'basic-lti-launch-request', $launch_url, [ 'User.id', 'Membership.role' ] ),
		];
		$optional_messages = [
			new Profile\Message( 'ContentItemSelectionRequest', $launch_url, [ 'User.id', 'Membership.role' ] ),
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
	 * Process a valid launch request
	 *
	 * Insert code here to handle incoming launches - use the user, context
	 * and resourceLink properties to access the current user, context and resource link.
	 */
	protected function onLaunch() {

	}

	/**
	 * Process a valid content-item request
	 *
	 * Insert code here to handle incoming content-item requests - use the user and context
	 * properties to access the current user and context.
	 */
	protected function onContentItem() {

		if ( in_array( 'application/vnd.ims.lti.v1.contentitems+json', $this->mediaTypes, true ) ) {
			// TODO: This specification doesn't seem widely supported? Returns multiple LtiLinkItem links at the same time
			// https://www.imsglobal.org/lti/model/mediatype/application/vnd/ims/lti/v1/contentitems%2Bjson/index.html
		}

		$this->ok = in_array( ToolProvider\ContentItem::LTI_LINK_MEDIA_TYPE, $this->mediaTypes, true ) || in_array( '*/*', $this->mediaTypes, true );
		if ( ! $this->ok ) {
			$this->reason = __( 'Return of an LTI link not offered', 'pressbooks-lti-provider' );
		} else {
			$this->ok = ! in_array( 'none', $this->documentTargets, true ) || ( count( $this->documentTargets ) > 1 );
			if ( ! $this->ok ) {
				$this->reason = __( 'No visible document target offered', 'pressbooks-lti-provider' );
			}
		}
		if ( ! $this->ok ) {
			$this->onError();
			return;
		}

		$_SESSION['pb_lti_data'] = $_POST['data'] ?? null;
		$_SESSION['pb_lti_consumer_pk'] = $this->consumer->getRecordId();
		$_SESSION['pb_lti_consumer_version'] = $this->consumer->ltiVersion;
		$_SESSION['pb_lti_return_url'] = $this->returnUrl;

		$html = blade()->render(
			'selection', [
				'title' => get_bloginfo( 'name' ),
				'url' => $this->baseUrl . 'format/lti/ContentItemSelection',
			]
		);

		$this->output = $html;
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

		// Show a page with options to redirect back to consumer

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

		$html = blade()->render(
			'register', [
				'title' => get_bloginfo( 'name' ),
				'success_url' => $success_url,
				'cancel_url' => $cancel_url,
			]
		);

		$this->output = $html;
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

}
