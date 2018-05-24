<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Lti\Provider\Modules\Export\ThinCC;

/**
 * More or less the same thing as 1.2 except for a few minor XML template tweaks to validate
 */
class CommonCartridge11 extends CommonCartridge12 {

	/**
	 * @var float
	 */
	protected $version = 1.1;

	/**
	 * @var string
	 */
	protected $suffix = '_1_1.imscc';
}
