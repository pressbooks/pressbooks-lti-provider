<?php

namespace Pressbooks\Lti\Provider;

use IMSGlobal\LTI\Profile;
use IMSGlobal\LTI\ToolProvider;

class Tool extends ToolProvider\ToolProvider {

	/**
	 * Tool constructor.
	 *
	 * @param $data_connector
	 */
	public function __construct( $data_connector ) {
		parent::__construct( $data_connector );

		$this->debugMode = WP_DEBUG;
		$this->baseUrl = network_site_url();

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

		$launch_url = '/path/to/lti'; // TODO
		$icon_url = 'images/icon50.png';

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
	 * Insert code here to handle incoming launches - use the user, context
	 * and resourceLink properties to access the current user, context and resource link.
	 */
	protected function onLaunch() {

	}

	/**
	 * Insert code here to handle incoming content-item requests - use the user and context
	 * properties to access the current user and context.
	 */
	protected function onContentItem() {

	}

	/**
	 * Insert code here to handle incoming registration requests - use the user
	 * property of the $tool_provider parameter to access the current user.
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

		// Redirect back to consumer
		$this->ok = true;
		$success = __( 'Successful registration', 'pressbooks-lti-provider' );
		$this->message = $success;
		$args = [
			'lti_msg' => $success,
			'tool_proxy_guid' => $this->consumer->getKey(),
		];
		$return_url = add_query_arg( $args, $this->returnUrl );
		$return_url = esc_url( $return_url );
		$this->redirectUrl = $return_url;
	}

	/**
	 * Insert code here to handle errors on incoming connections - do not expect
	 * the user, context and resourceLink properties to be populated but check the reason
	 * property for the cause of the error.  Return TRUE if the error was fully
	 * handled by this method.
	 */
	protected function onError() {

	}

}
