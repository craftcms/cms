<?php

Craft\Craft::requirePackage(Craft\CraftPackage::Cloud);

class Opencloud {

	const authUrl = 'https://identity.api.rackspacecloud.com/v2.0/';
	const serviceName = 'cloudFiles';

	/**
	 * Connect to RackSpace.
	 *
	 * @param $username
	 * @param $apiKey
	 * @return \OpenCloud\Rackspace
	 */
	public static function connect($username, $apiKey)
	{
		require_once(__DIR__.'/Opencloud/rackspace.php');
		return new \OpenCloud\Rackspace(self::authUrl, array('username' => $username, 'apiKey' => $apiKey));
	}
}