<?php
/**
 * Defines a virtual network
 *
 * @copyright 2012-2013 Rackspace Hosting, Inc.
 * See COPYING for licensing information
 *
 * @package phpOpenCloud
 * @version 1.0
 * @author Glen Campbell <glen.campbell@rackspace.com>
 */

namespace OpenCloud\Compute;

require_once(__DIR__.'/persistentobject.php');
require_once(__DIR__.'/metadata.php');

/**
 * The Network class represents a single virtual network
 *
 * @api
 * @author Glen Campbell <glen.campbell@rackspace.com>
 */
class Network extends \OpenCloud\PersistentObject {

	public
		$id,
		$label,
		$cidr;
	
	protected static
		$json_name = 'network',
		$url_resource = 'os-networksv2';

	/**
	 * Creates a new isolated Network object
	 *
	 * NOTE: contains hacks to recognize the Rackspace public and private
	 * networks. These are not really networks, but they show up in lists.
	 *
	 * @param \OpenCloud\Compute $service The compute service associated with
	 *      the network
	 * @param string $id The ID of the network (this handles the pseudo-networks
	 *		RAX_PUBLIC and RAX_PRIVATE
	 * @return void
	 */
	public function __construct(\OpenCloud\Compute $service, $id=NULL) {
		$this->id = $id;
		switch($id) {
		case RAX_PUBLIC:
			$this->label = 'public';
			$this->cidr = 'NA';
			return;
		case RAX_PRIVATE:
			$this->label = 'private';
			$this->cidr = 'NA';
			return;
		default:
			return parent::__construct($service, $id);
		}
	}

	/**
	 * Always throws an error; updates are not permitted
	 *
	 * @throws NetworkUpdateError always
	 */
	public function Update($params=array()) {
		throw new NetworkUpdateError(_('Isolated networks cannot be updated'));
	}

	/**
	 * Deletes an isolated network
	 *
	 * @api
	 * @return \OpenCloud\HttpResponse
	 * @throws NetworkDeleteError if HTTP status is not Success
	 */
	public function Delete() {
		switch($this->id) {
		case RAX_PUBLIC:
		case RAX_PRIVATE:
			throw new \OpenCloud\DeleteError(_('Network may not be deleted'));
		default:
			return parent::Delete();
		}
	}
	
	/**
	 * returns the visible name (label) of the network
	 *
	 * @api
	 * @return string
	 */
	public function Name() {
		return $this->label;
	}

	/********** PROTECTED METHODS **********/

	/**
	 * Creates the JSON object for the Create() method
	 */
	protected function CreateJson() {
		$obj = new \stdClass();
		$obj->network = new \stdClass();
		$obj->network->cidr = $this->cidr;
		$obj->network->label = $this->label;
		return $obj;
	}

} // class Network
