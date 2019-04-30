<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace PressbooksLtiProvider\Entities;

class User {
	/**
	 * @var \WP_User
	 */
	public $user;

	/**
	 * @var string
	 */
	public $ltiId;

	/**
	 * @var bool
	 */
	public $ltiIdWasMatched;

	/**
	 * @var string
	 */
	public $role;

	/**
	 * @var array
	 */
	public $params;
}
