<?php

namespace PressbooksLtiProvider;

use IMSGlobal\LTI\ToolProvider;

class Controller {

	/**
	 * @var Admin
	 */
	protected $admin;

	/**
	 * @var Entities\Storage
	 */
	protected $storage;

	/**
	 * Controller constructor.
	 *
	 * @param Admin $admin
	 */
	public function __construct( Admin $admin ) {
		$this->admin = $admin;
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

		// Check if we stored info while jerking the user around for security purposes
		if ( isset( $_SESSION['pb_lti_prompt_for_authentication'] ) ) {
			if ( $_SESSION['pb_lti_prompt_for_authentication'] instanceof Entities\Storage ) {
				$this->storage = $_SESSION['pb_lti_prompt_for_authentication'];
			}
			unset( $_SESSION['pb_lti_prompt_for_authentication'] ); // Unset, don't reuse
		}

		switch ( $action ) {
			case 'contentItemSubmit':
				$this->contentItemSubmit( $params );
				break;
			case 'createbook':
				$this->createBook( $action, $params );
				break;
			default:
				$this->default( $action, $params );
		}
	}

	/**
	 * @param array $params
	 */
	public function contentItemSubmit( $params ) {
		if ( empty( $_SESSION['pb_lti_consumer_pk'] ) || empty( $_SESSION['pb_lti_consumer_version'] ) || empty( $_SESSION['pb_lti_return_url'] ) ) {
			wp_die( __( 'You do not have permission to do that.' ) );
		}

		$title = get_bloginfo( 'name' );
		$url = deep_link();
		if ( ! empty( $_POST['section'] ) ) {
			$post_id = (int) $_POST['section'];
			$title = get_the_title( $post_id );
			$url .= "/{$post_id}";
		}

		$item = new ToolProvider\ContentItem( 'LtiLinkItem' );
		$item->setMediaType( ToolProvider\ContentItem::LTI_LINK_MEDIA_TYPE );
		$item->setTitle( $title );
		$item->setText( 'Returning a link from Pressbooks to see what happens' );
		$item->setUrl( $url );

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

	/**
	 * @param $action
	 * @param $params
	 */
	public function createBook( $action, $params ) {
		// TC creates anonymous LTI launch request
		$connector = Database::getConnector();
		$tool      = new Tool( $connector );
		$tool->setAdmin( $this->admin );
		$tool->setAction( $action );
		// TP validates LTI launch and creates tool state
		$tool->processRequest( $params );
		// authenticates, sets session variables and sets up user
		$tool->handleRequest();

		// Creates a url from the name of the activity link
		$activity_url = $tool->buildAndValidateUrl( $_POST['resource_link_title'] );
		$exists       = $tool->validateLtiBookExists( $activity_url, $_POST['resource_link_id'] );

		// Display book, if the book already exists and was created by the same resource_link_id
		if ( $exists && is_user_logged_in() && $this->storage && (int) $this->storage->user->ID === (int) wp_get_current_user()->ID ) {
			// TODO: Return the book for viewing pleasure
		}

		$new_book_url = $tool->maybeDisambiguateDomain( $activity_url );
		$title        = $tool->buildTitle( $_POST['context_label'], $_POST['context_id'], $_POST['resource_link_title'], $_POST['resource_link_id'] );

		// user
		$wp_user = $tool->matchUserById( $tool->consumer->consumerGuid . $tool->user->getId() );

		// try to create a book
		if ( $wp_user ) {
			try {
				$book_id = $tool->createNewBook( $new_book_url, $title, $wp_user->ID, $_POST['resource_link_id'], $_POST['context_id'] );
			} catch ( \Exception $e ) {
				$tool->ok      = false;
				$tool->message = __( 'Sorry, a book could not be created', 'pressbooks-lti-provider' );
				$tool->handleRequest();
			}

			$tool->loginUser( $wp_user, $tool->user->getId(), true, $tool->user->roles[0] );

		}

	}

	/**
	 * @param $tool
	 * @param $args
	 *
	 * @return mixed|void
	 */
	public function userMatch( $tool, $args ) {

		$wp_user = $tool->fuzzyUserMatch( $args );

		// create a new user if none found
		if ( false === $wp_user ) {
			try {
				$new_user = $tool->createUser( $args['ext_user_username'], $args['lis_person_contact_email_primary'] );
			} catch ( \Exception $e ) {
				// TODO: Decide how to handle exceptions
				return;
			}
			if ( $new_user ) {
				$wp_user = get_user_by( 'ID', $new_user[0] );
			}
		}

		return $wp_user;
	}

	/**
	 * @param string $action
	 * @param array $params
	 */
	public function default( $action, $params ) {
		$connector = Database::getConnector();
		$tool = new Tool( $connector );
		$tool->setAdmin( $this->admin );
		$tool->setAction( $action );
		if (
			$action === 'launch' && is_user_logged_in() && $this->storage &&
			(int) $this->storage->user->ID === (int) wp_get_current_user()->ID
		) {
			// User has confirmed matching with existing account
			$tool->setParams( $this->storage->params );
			$tool->loginUser( $this->storage->user, $this->storage->ltiId, $this->storage->ltiIdWasMatched, $this->storage->role );
			$tool->setupDeepLink();
			\Pressbooks\Redirect\location( $tool->getRedirectUrl() );
		} else {
			$tool->processRequest( $params );
			$tool->handleRequest();
		}
	}

}
