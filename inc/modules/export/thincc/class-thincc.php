<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Lti\Provider\Modules\Export\ThinCC;

use Pressbooks\Modules\Export\Export;

class ThinCC extends Export {

	/**
	 * Common Cartridge Version
	 *
	 * @var float
	 */
	protected $version = 1.2;

	/**
	 * Mandatory convert method, create $this->outputPath
	 *
	 * @return bool
	 */
	public function convert() {
		// TODO: Implement convert() method.
	}

	/**
	 * Mandatory validate method, check the sanity of $this->outputPath
	 *
	 * @return bool
	 */
	public function validate() {
		// TODO: Implement validate() method.
	}
}
