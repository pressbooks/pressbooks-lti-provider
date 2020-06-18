<?php

namespace PressbooksLtiProvider;

/**
 * Class Book
 * @package PressbooksLtiProvider
 */
class Createbook {
	/**
	 * The URL of the new book.
	 *
	 * @since 1.4.0
	 *
	 * @var string
	 */
	protected $domain;

	/**
	 * The path of the new book.
	 *
	 * @since 1.4.0
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * The title of the new book.
	 *
	 * @since 1.4.0
	 *
	 * @var string
	 */
	protected $bookTitle;

	/**
	 * The user ID of the new book's admin.
	 *
	 * @since 1.4.0
	 *
	 * @var int
	 */
	protected $userId;

	/**
	 * The payload from LTI Consumer
	 *
	 * @since 1.4.0
	 *
	 * @var array
	 */
	protected $payload = [];

	/**
	 * Represents minimum information needed to set up a book
	 *
	 * @since 1.4.0
	 *
	 * @var array
	 */
	protected $expected = [
		'resource_link_title'              => '',
		'resource_link_id'                 => '',
		'context_label'                    => '',
		'context_id'                       => '',
		'user_id'                          => '',
		'lis_person_contact_email_primary' => '',
		'lis_person_name_given'            => '',
		'lis_person_name_family'           => '',
		'lis_person_name_full'             => '',
		'roles'                            => '',
		'launch_presentation_return_url'   => ''
	];

	/**
	 * Createbook constructor.
	 *
	 * @param Tool $tool \PressbooksLtiProvider\Tool must be ok
	 * @param $payload the data coming in from the LTI launch
	 */
	public function __construct( Tool $tool, $payload ) {

		if ( false === $tool->ok ) {
			return false;
		}
		if ( is_array( $payload ) ) {
			$this->payload = $payload;
		}
		// merge, transfer values
		wp_parse_args( $payload, $this->expected );
	}

	protected function build() {
		$new_book_url = $this->buildAndValidateUrl( $this->expected['resource_link_title'] );
		$title        = $this->buildTitle( $this->expected['context_label'], $this->expected['context_id'], $this->expected['resource_link_title'], $this->expected['resource_link_id'] );

	}

	protected function buildAndValidateUrl( $activity_name ) {
		// only lower case and numbers

		// at least some letters

		// illegal names

		// at least 4 characters

		// check against existing subdomains

		// check against existing domains

	}

	/**
	 * Builds a book title from expected values and applies an opinionated format.
	 * Supplies default values should arguments be empty
	 *
	 * @param $course_name
	 * @param $course_id
	 * @param $activity_name
	 * @param $activity_id
	 *
	 * @return string
	 * @since 1.4.0
	 *
	 */
	protected function buildTitle( $course_name, $course_id, $activity_name, $activity_id ) {
		$course   = ( ! empty( $course_name ) ? $course_name : 'Course ' . $course_id );
		$activity = ( ! empty( $activity_name ) ? $activity_name : 'Activity ' . $activity_id );

		$title = sprintf( '%1$s: %2$s', $course, $activity );

		return sanitize_title( $title, 'Untitled' );
	}

// create the blog if it's a staff member and blog provisioning is on

// handle errors, if it fails

}
