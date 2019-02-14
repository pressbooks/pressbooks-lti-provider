<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace PressbooksLtiProvider\Modules\Export\ThinCC;

/**
 * More or less the same thing as 1.2 except for a few minor XML template tweaks
 */
class CommonCartridge11 extends CommonCartridge12 {

	/**
	 * @var string
	 */
	protected $version = '1.1';

	/**
	 * @var string
	 */
	protected $suffix = '_1_1.imscc';
}
