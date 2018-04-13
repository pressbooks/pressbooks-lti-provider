<?php

namespace Pressbooks\Lti\Provider;

use IMSGlobal\LTI\ToolProvider;

class Controller {

	public function __construct() {
	}

	/**
	 * @param string $action
	 * @param array $params
	 */
	public function handleRequest( $action, $params ) {

		if ( function_exists( 'wp_magic_quotes' ) ) {
			// Thanks but no thanks WordPress...
			$_GET = stripslashes_deep( $_GET );
			$_POST = stripslashes_deep( $_POST );
			$_COOKIE = stripslashes_deep( $_COOKIE );
			$_SERVER = stripslashes_deep( $_SERVER );
		}

		switch ( $action ) {
			case 'contentItemSubmit':
				$this->contentItemSubmit( $params );
				break;
			default:
				$this->default( $action, $params );
		}
	}

	/**
	 * @param string $action
	 * @param array $params
	 */
	public function default( $action, $params ) {
		$connector = Database::getConnector();
		$tool = new Tool( $connector );
		$tool->setAction( $action );
		$tool->setParams( $params );
		$tool->setParameterConstraint( 'oauth_consumer_key', true, 50, [ 'basic-lti-launch-request', 'ContentItemSelectionRequest' ] );
		$tool->setParameterConstraint( 'resource_link_id', true, 50, [ 'basic-lti-launch-request' ] );
		$tool->setParameterConstraint( 'user_id', true, 50, [ 'basic-lti-launch-request' ] );
		$tool->setParameterConstraint( 'roles', true, null, [ 'basic-lti-launch-request' ] );
		$tool->handleRequest();
	}

	/**
	 * @param array $params
	 */
	public function contentItemSubmit( $params ) {
		if ( empty( $_SESSION['pb_lti_consumer_pk'] ) || empty( $_SESSION['pb_lti_consumer_version'] ) || empty( $_SESSION['pb_lti_return_url'] ) ) {
			wp_die( __( 'You do not have permission to do that.' ) );
		}

		$item = new ToolProvider\ContentItem( 'LtiLinkItem' );
		$item->setMediaType( ToolProvider\ContentItem::LTI_LINK_MEDIA_TYPE );
		$item->setTitle( 'Shie Kasai' );
		$item->setText( "Returning a link to Shie's web comic to see what happens" );
		$item->setUrl( 'https://manga.shiekasai.com' );

		$form_params['content_items'] = ToolProvider\ContentItem::toJson( $item );
		if ( ! is_null( $_SESSION['pb_lti_data'] ) ) {
			$form_params['data'] = $_SESSION['pb_lti_data'];
		}
		$data_connector = Database::getConnector();
		$consumer = ToolProvider\ToolConsumer::fromRecordId( $_SESSION['pb_lti_consumer_pk'], $data_connector );
		$form_params = $consumer->signParameters( $_SESSION['pb_lti_return_url'], 'ContentItemSelection', $_SESSION['pb_lti_consumer_version'], $form_params );
		$page = ToolProvider\ToolProvider::sendForm( $_SESSION['pb_lti_return_url'], $form_params );
		echo $page;
	}

}
