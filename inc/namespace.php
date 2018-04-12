<?php

namespace Pressbooks\Lti\Provider;

use IMSGlobal\LTI\ToolProvider;

/**
 * Generate a globally unique identifier (GUID)
 *
 * @return string
 */
function globally_unique_identifier() {
	$option = 'pressbooks_lti_GUID';
	$guid = get_site_option( $option );
	if ( ! $guid ) {
		if ( function_exists( 'com_create_guid' ) === true ) {
			$guid = trim( com_create_guid(), '{}' );
		} else {
			$guid = sprintf( '%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand( 0, 65535 ), mt_rand( 0, 65535 ), mt_rand( 0, 65535 ), mt_rand( 16384, 20479 ), mt_rand( 32768, 49151 ), mt_rand( 0, 65535 ), mt_rand( 0, 65535 ), mt_rand( 0, 65535 ) );
		}
		update_site_option( $option, $guid );
	}
	return $guid;
}

/**
 * Processes a launch request from an LTI tool consumer
 *
 * Hooked into action: pb_do_format
 *
 * @param $format string
 */
function do_format( $format ) {

	$params = explode( '/', $format );
	$controller = array_shift( $params );
	$action = array_shift( $params );

	if ( 'lti' !== $controller ) {
		return; // Bail
	}

	if ( $action === 'ContentItemSelection' ) {

		if ( empty( $_SESSION['consumer_pk'] ) || empty( $_SESSION['lti_version'] ) || empty( $_SESSION['return_url'] ) ) {
			wp_die( __( 'You do not have permission to do that.' ) );
		}

		$item = new ToolProvider\ContentItem( 'LtiLinkItem' );
		$item->setMediaType( ToolProvider\ContentItem::LTI_LINK_MEDIA_TYPE );
		$item->setTitle( 'Shie Kasai' );
		$item->setText( "Returning a link to Shie's web comic to see what happens" );
		$item->setUrl( 'https://manga.shiekasai.com' );

		$form_params['content_items'] = ToolProvider\ContentItem::toJson( $item );
		if ( ! is_null( $_SESSION['data'] ) ) {
			$form_params['data'] = $_SESSION['data'];
		}
		$data_connector = Database::getConnector();
		$consumer = ToolProvider\ToolConsumer::fromRecordId( $_SESSION['consumer_pk'], $data_connector );
		$form_params = $consumer->signParameters( $_SESSION['return_url'], 'ContentItemSelection', $_SESSION['lti_version'], $form_params );
		$page = ToolProvider\ToolProvider::sendForm( $_SESSION['return_url'], $form_params );
		echo $page;

	} else {

		// @codingStandardsIgnoreStart
		if ( function_exists( 'wp_magic_quotes' ) ) {
			// Thanks but no thanks WordPress...
			$_GET = stripslashes_deep( $_GET );
			$_POST = stripslashes_deep( $_POST );
			$_COOKIE = stripslashes_deep( $_COOKIE );
			$_SERVER = stripslashes_deep( $_SERVER );
		}
		// @codingStandardsIgnoreEnd

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

	exit;
}

/**
 * @return \Jenssegers\Blade\Blade
 */
function blade() {
	static $blade;
	if ( empty( $blade ) ) {
		$views = __DIR__ . '/../templates';
		$cache = \Pressbooks\Utility\get_cache_path();
		$blade = new \Jenssegers\Blade\Blade( $views, $cache );
	}
	return $blade;
}
