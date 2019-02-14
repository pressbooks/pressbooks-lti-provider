<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace PressbooksLtiProvider\Modules\Export\ThinCC;

class CommonCartridge13 extends CommonCartridge12 {

	/**
	 * @var string
	 */
	protected $version = '1.3';

	/**
	 * @var string
	 */
	protected $suffix = '_1_3.imscc';

	/**
	 * @param int $id
	 * @param string $title
	 * @param string $view
	 *
	 * @return array
	 */
	public function getData( $id, $title, $view ) {
		$data = parent::getData( $id, $title, $view );
		$data['identifier'] = $this->identifier( $id );
		$data['points_possible'] = 10; // TODO
		return $data;
	}

	/**
	 * @param int $post_id
	 * @param string $title
	 *
	 * @return string
	 */
	public function getView( $post_id, $title ) {
		if ( $this->isAssignment( $post_id, $title ) ) {
			return 'assignment';
		} else {
			return parent::getView( $post_id, $title );
		}
	}

	/**
	 * @param int $post_id
	 * @param string $title
	 *
	 * @return string
	 */
	public function getResourceType( $post_id, $title ) {
		if ( $this->isAssignment( $post_id, $title ) ) {
			return 'assignment_xmlv1p0';
		} else {
			return 'imsbasiclti_xmlv1p3';
		}
	}

	/**
	 * @param int $post_id
	 * @param string $title
	 *
	 * @return bool
	 */
	public function isAssignment( $post_id, $title ) {
		// Backwards compatibility with SteelWagstaff/candela-thin-exports` (forked from `lumenlearning/candela-thin-exports)
		if ( 0 === strpos( $title, 'Assignment:' ) ) {
			return true;
		}
		return false;
	}
}
