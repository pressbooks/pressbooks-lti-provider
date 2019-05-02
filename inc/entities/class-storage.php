<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace PressbooksLtiProvider\Entities;

/**
 * An object we store in $_SESSION['pb_lti_prompt_for_authentication']
 */
class Storage {
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

	/**
	 * @var string
	 */
	public $lmsName;
}
