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
		if ( $exists && $tool->user->isLearner() ) {
			// TODO: Return the book for viewing pleasure
		}

		$new_book_url = $tool->maybeDisambiguateDomain( $activity_url );
		$title        = $tool->buildTitle( $_POST['context_label'], $_POST['context_id'], $_POST['resource_link_title'], $_POST['resource_link_id'] );

		// user
		$lti_id = "{$tool->consumer->consumerGuid}|{$tool->user->getId()}";
		$wp_user = $tool->matchUserById( $lti_id );

		// try to create a book
		if ( $wp_user ) {
			$lti_id_was_matched = true;

			// restrict book creation to staff or admins
			if ( $tool->user->isStaff() || $tool->user->isAdmin() ) {
				try {
					$book_id = $tool->createNewBook( $new_book_url, $title, $wp_user->ID, $_POST['resource_link_id'], $_POST['context_id'] );
				} catch ( \Exception $e ) {
					$tool->ok      = false;
					$tool->message = __( 'Sorry, a book could not be created', 'pressbooks-lti-provider' );
					$tool->handleRequest();
				}
			}
			// Role
			$settings = $this->admin->getBookSettings();
			if ( $tool->user->isAdmin() ) {
				$role = $settings['admin_default'];
			} elseif ( $$tool->user->isStaff() ) {
				$role = $settings['staff_default'];
			} elseif ( $$tool->user->isLearner() ) {
				$role = $settings['learner_default'];
			} else {
				$role = 'anonymous';
			}
			$tool->loginUser( $wp_user, $lti_id, $lti_id_was_matched, $role );
		}

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
