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
				$connector = Database::getConnector();
				$tool      = new Tool( $connector );
				$tool->setAdmin( $this->admin );
				$tool->setAction( $action );
				$tool->processRequest( $params );
				$user_id = false;

				// create a url from the name of the activity link
				$activity_url = $tool->buildAndValidateUrl( $_POST['resource_link_title'] );
				if ( false === $activity_url ) {
					// TODO: Return the book for viewing pleasure
					$tool->handleRequest();
					do_exit();
				}
				$exists = $tool->validateLtiBookExists( $activity_url, $_POST['resource_link_id'] );

				// do not create if the book exists and was created by the same resource_link_id
				if ( $exists ) {
					// TODO: Return the book for viewing pleasure
					$tool->handleRequest();
					do_exit();
				}

				$new_book_url = $tool->maybeDisambiguateDomain( $activity_url );
				$title        = $tool->buildTitle( $_POST['context_label'], $_POST['context_id'], $_POST['resource_link_title'], $_POST['resource_link_id'] );

				// user match starts here.
				$wp_user = $tool->matchUserById( $_POST['tool_consumer_instance_guid'] . $_POST['user_id'] );

				if ( ! $wp_user ) {
					$user_id = $tool->fuzzyUserMatch( $_POST );
				}

				if ( false === $user_id ) {
					try {
						$new_user = $tool->createUser( $_POST['ext_user_username'], $_POST['lis_person_contact_email_primary'] );
					} catch ( \Exception $e ) {
						return; // TODO: Decide how to handle exceptions
					}

					$user_id = $new_user[0];
				}

				// try to create a book
				if ( $user_id ) {
					try {
						$tool->createNewBook( $new_book_url, $title, $user_id->ID, $_POST['resource_link_id'], $_POST['context_id'] );
					} catch ( \Exception $e ) {
						return; // TODO: Decide how to handle exceptions
					}
				}

				// bail put error messages in front of Consumer.
				if ( false === $tool->ok ) {
					$tool->handleRequest();
					do_exit();
					break;
				}

				// log in user to book
				$tool->user = new ToolProvider\User();
				$tool->user->setEmail( $_POST['lis_person_contact_email_primary'] );
				$tool->user->setNames( $_POST['lis_person_name_given'], $_POST['lis_person_name_family'], $_POST['lis_person_name_full'] );
				$tool->user->setRecordId( $_POST['user_id'] );
				$tool->user->setResourceLinkId( $_POST['resource_link_id'] );
				// how to give the user an id
				$tool->setupUser( $tool->user, $_POST['tool_consumer_instance_guid'] );

				$tool->handleRequest();
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
