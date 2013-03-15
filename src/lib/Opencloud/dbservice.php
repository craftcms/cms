<?php
/**
 * Rackspace's Cloud Databases (database as a service)
 *
 * @copyright 2012-2013 Rackspace Hosting, Inc.
 * See COPYING for licensing information
 *
 * @package phpOpenCloud
 * @version 1.0
 * @author Glen Campbell <glen.campbell@rackspace.com>
 */

namespace OpenCloud;

require_once(__DIR__.'/nova.php');
require_once(__DIR__.'/instance.php');

/**
 * The Rackspace Database As A Service (aka "Red Dwarf")
 *
 * @author Glen Campbell <glen.campbell@rackspace.com>
 */
class DbService extends Nova {

	/**
	 * Creates a new DbService service connection
	 *
	 * This is not normally called directly, but via the factory method on the
	 * OpenStack or Rackspace connection object.
	 *
	 * @param OpenStack $conn the connection on which to create the service
	 * @param string $name the name of the service (e.g., "cloudDatabases")
	 * @param string $region the region of the service (e.g., "DFW" or "LON")
	 * @param string $urltype the type of URL (normally "publicURL")
	 */
	public function __construct(OpenStack $conn, $name, $region, $urltype) {
		parent::__construct($conn, 'rax:database', $name, $region, $urltype);
	}

	/**
	 * Returns the URL of this database service, or optionally that of
	 * an instance
	 *
	 * @param string $resource the resource required
	 * @param array $args extra arguments to pass to the URL as query strings
	 */
	public function Url($resource='instances', $args=array()) {
		return parent::Url($resource, $args);
	}

	/**
	 * Returns a list of flavors
	 *
	 * just call the parent FlavorList() method, but pass FALSE
	 * because the /flavors/detail resource is not supported
	 *
	 * @api
	 * @return \OpenCloud\Compute\FlavorList
	 */
	public function FlavorList($details=FALSE, $filter=array()) {
	    return parent::FlavorList(FALSE);
	}

	/**
	 * Creates a Instance object
	 *
	 * @api
	 * @param string $id the ID of the instance to retrieve
	 * @return DbService\Instance
	 */
	public function Instance($id=NULL) {
		return new DbService\Instance($this, $id);
	}

	/**
	 * Creates a Collection of Instance objects
	 *
	 * @api
	 * @param array $params array of parameters to pass to the request as
	 *      query strings
	 * @return Collection
	 */
	public function InstanceList($params=array()) {
		return $this->Collection(
			'\OpenCloud\DbService\Instance', NULL, NULL, $params);
	}
}
