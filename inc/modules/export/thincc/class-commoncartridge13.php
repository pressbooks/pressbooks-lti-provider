<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Lti\Provider\Modules\Export\ThinCC;

class CommonCartridge13 extends CommonCartridge12 {

	/**
	 * @var float
	 */
	protected $version = 1.3;

	/**
	 * @var string
	 */
	protected $suffix = '_1_3.zip';


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
		$data['points_possible'] = 10;
		return $data;
	}

	/**
	 * @param string $title
	 *
	 * @return string
	 */
	public function getView( $title ) {
		if ( $this->isAssignment( $title ) ) {
			return 'assignment';
		} else {
			return parent::getView( $title );
		}
	}

	/**
	 * @param string $title
	 *
	 * @return string
	 */
	public function getResourceType( $title ) {
		if ( $this->isAssignment( $title ) ) {
			return 'assignment_xmlv1p0';
		} elseif ( $this->isDiscussion( $title ) ) {
			return 'imsdt_xmlv1p3';
		} else {
			return 'imsbasiclti_xmlv1p0';
		}
	}

	/**
	 * @param string $title
	 *
	 * @return bool
	 */
	public function isAssignment( $title ) {
		return ( 0 === strpos( $title, 'Assignment:' ) );
	}
}
