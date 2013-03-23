<?php
/**
 * Defines a block storage volume type
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
 * The VolumeType class represents a single block storage volume type
 *
 * @api
 * @author Glen Campbell <glen.campbell@rackspace.com>
 */
class VolumeType extends \OpenCloud\PersistentObject {

	public
		$id,
		$name,
		$extra_specs;
		
	protected static
		$json_name = 'volume_type',
		$url_resource = 'types';

	/**
	 * Creates are not permitted
	 *
	 * @throws OpenCloud\CreateError always
	 */
	public function Create($params=array()) {
		throw new \OpenCloud\CreateError(
			_('VolumeType cannot be created'));
	}

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
	 * deletes are not permitted
	 *
	 * @throws OpenCloud\DeleteError
	 */
	public function Delete() {
		throw new \OpenCloud\DeleteError(
			_('VolumeType cannot be deleted'));
	}

}
