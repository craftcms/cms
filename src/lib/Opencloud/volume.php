<?php
/**
 * Defines a block storage volume
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
 * The Volume class represents a single block storage volume
 *
 * @api
 * @author Glen Campbell <glen.campbell@rackspace.com>
 */
class Volume extends \OpenCloud\PersistentObject {

	public
		$id,
		$status,
		$display_name,
		$display_description,
		$size,
		$volume_type,
		$metadata = array(),
		$availability_zone,
		$snapshot_id,
		$attachments = array(),
		$created_at;
	
	protected static
		$json_name = 'volume',
		$url_resource = 'volumes';

	private
		$_create_keys = array(
		    'snapshot_id',
			'display_name',
			'display_description',
			'size',
			'volume_type',
			'availability_zone'
		);

	/**
	 * Always throws an error; updates are not permitted
	 *
	 * @throws OpenCloud\UpdateError always
	 */
	public function Update($params=array()) {
		throw new \OpenCloud\UpdateError(
			_('Block storage volumes cannot be updated'));
	}

	/**
	 * returns the name of the volume
	 *
	 * @api
	 * @return string
	 */
	public function Name() {
		return $this->display_name;
	}

	/********** PROTECTED METHODS **********/

	/**
	 * Creates the JSON object for the Create() method
	 *
	 * @return stdClass
	 */
	protected function CreateJson() {
		$element = $this->JsonName();
		$obj = new \stdClass();
		$obj->$element = new \stdClass();
		foreach ($this->_create_keys as $name) {
			if ($this->$name) {
			    switch($name) {
			    case 'volume_type':
			        $obj->$element->$name = $this->volume_type->Name();
			        break;
			    default:
				    $obj->$element->$name = $this->$name;
				}
			}
		}
		if (is_array($this->metadata) && count($this->metadata)) {
			$obj->$element->metadata = new \stdClass();
			foreach($this->metadata as $key => $value)
				$obj->$element->metadata->$key = $value;
		}
		return $obj;
	}

} // class Volume
