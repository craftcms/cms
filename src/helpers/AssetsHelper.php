<?php
namespace Craft;

/**
 *
 */
class AssetsHelper
{
	const ActionKeepBoth = 'keep_both';
	const ActionReplace = 'replace';
	const ActionCancel = 'cancel';
	const IndexSkipItemsPattern = '/.*(Thumbs\.db|__MACOSX|__MACOSX\/|__MACOSX\/.*|\.DS_STORE)$/i';

	const RackspaceAuthUrl = 'https://identity.api.rackspacecloud.com/v2.0/';

	/**
	 * Get a temporary file path.
	 *
	 * @param string $extension extension to use. "tmp" by default.
	 * @return mixed
	 */
	public static function getTempFilePath($extension = 'tmp')
	{
		$extension = preg_replace('/[^a-z]/i', '', $extension);
		$fileName = uniqid('assets', true) . '.' . $extension;

		return IOHelper::createFile(craft()->path->getTempPath() . $fileName)->getRealPath();
	}

	/**
	 * Connect to RackSpace.
	 *
	 * @param $username
	 * @param $apiKey
	 * @return \OpenCloud\Rackspace|null
	 */
	public static function connectToRackspace($username, $apiKey)
	{
		if (Craft::hasPackage(CraftPackage::Cloud))
		{
			require_once(craft()->path->getLibPath().'Opencloud/rackspace.php');
			return new \OpenCloud\Rackspace(self::RackspaceAuthUrl, array('username' => $username, 'apiKey' => $apiKey));
		}

		return null;
	}
}

