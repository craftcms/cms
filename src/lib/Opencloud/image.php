<?php
/**
 * An object that defines a virtual machine image
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

/**
 * The Image class represents a stored machine image returned by the
 * Compute service.
 *
 * In the future, this may be abstracted to access
 * Glance (the OpenStack image store) directly, but it is currently
 * not available to Rackspace customers, so we're using the /images
 * resource on the servers API endpoint.
 */
class Image extends \OpenCloud\PersistentObject {

    public
		$status,
		$updated,
		$links,
		$minDisk,
		$id,
		$name,
		$created,
		$progress,
		$minRam,
		$metadata,
		$server;

	protected static
		$json_name = 'image',
		$url_resource = 'images';

	public function Create($params=array()) { $this->NoCreate(); }
	public function Update($params=array()) { $this->NoUpdate(); }

} // class Image
