<?php
/**
 * Defines a block storage snapshot
 *
 * @copyright 2012-2013 Rackspace Hosting, Inc.
 * See COPYING for licensing information
 *
 * @package phpOpenCloud
 * @version 1.0
 * @author Glen Campbell <glen.campbell@rackspace.com>
 */

namespace OpenCloud\VolumeService;

require_once(__DIR__.'/persistentobject.php');
require_once(__DIR__.'/metadata.php');

/**
 * The Snapshot class represents a single block storage snapshot
 *
 * @api
 * @author Glen Campbell <glen.campbell@rackspace.com>
 */
class Snapshot extends \OpenCloud\PersistentObject {

	public
		$id,
		$display_name,
		$display_description,
		$volume_id,
		$status,
		$size,
		$created_at;

	protected
		$force=FALSE;

	protected static
		$json_name = 'snapshot',
		$url_resource = 'snapshots';

	private
		$_create_keys=array('display_name','display_description',
							'volume_id','force');

	/**
	 * updates are not permitted
	 *
	 * @throws OpenCloud\UpdateError always
	 */
	public function Update($params=array()) {
		throw new \OpenCloud\UpdateError(
			_('VolumeType cannot be updated'));
	}

	/**
	 * returns the display_name attribute
	 *
	 * @api
	 * @return string
	 */
	public function Name() {
	    return $this->display_name;
	}

	/********** PROTECTED METHODS **********/

	/**
	 * returns the object for the Create() method's JSON
	 *
	 * @return stdClass
	 */
	protected function CreateJson() {
		$obj = new \stdClass();

		$elem = $this->JsonName();
		$obj->$elem = new \stdClass();
		foreach($this->_create_keys as $key)
            $obj->$elem->$key = $this->$key;

		return $obj;
	}

}
