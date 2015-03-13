<?php
namespace craft\app\assetsourcetypes;

use Craft;
use craft\app\enums\AttributeType;
use craft\app\io\flysystemadapters\Rackspace as RackspaceAdapter;
use \OpenCloud\OpenStack;
use \OpenCloud\Rackspace as RackspaceClient;


/**
 * The Rackspace Cloud source type class. Handles the implementation of the Rackspace Cloud Storage service as an asset source type in
 * Craft.
 *
 * @author     Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright  Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license    http://buildwithcraft.com/license Craft License Agreement
 * @see        http://buildwithcraft.com
 * @package    craft.app.assetsourcetypes
 * @since      1.0
 */
class Rackspace extends BaseAssetSourceType
{
	// Properties
	// =========================================================================

	/**
	 * Whether this is a local source or not. Defaults to false.
	 *
	 * @var bool
	 */
	protected $isSourceLocal = false;

	/**
	 * Set to true if the Adapter expects folder names to have trailing slashes
	 *
	 * @var bool
	 */
	protected $foldersHaveTrailingSlashes = false;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc IComponentType::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('app', 'Rackspace Cloud Files');
	}

	/**
	 * @inheritDoc ISavableComponentType::getSettingsHtml()
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		$settings = $this->getSettings();

		return Craft::$app->templates->render('_components/assetsourcetypes/Rackspace/settings', array(
			'settings' => $settings
		));
	}

	/**
	 * Get the container list list using the specified credentials for the region.
	 *
	 * @param $username
	 * @param $apiKey
	 * @param $region
	 *
	 * @throws \InvalidArgumentException
	 * @return array
	 */
	public static function loadContainerList($username, $apiKey, $region)
	{
		if (empty($username) || empty($apiKey) || empty($region))
		{
			throw new \InvalidArgumentException(Craft::t('app', 'You must specify a username, the API key and a region to get the container list.'));
		}

		$client = static::getClient($username, $apiKey);

		$service = $client->objectStoreService('cloudFiles', $region);

		$containerList = $service->getCdnService()->listContainers();

		$returnData = array();

		while ($container = $containerList->next())
		{
			$returnData[] = (object) array('container' => $container->name, 'urlPrefix' => rtrim($container->getCdnUri(), '/').'/');
		}

		return $returnData;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseSavableComponentType::defineSettings()
	 *
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'username'   => array(AttributeType::String, 'required' => true),
			'apiKey'     => array(AttributeType::String, 'required' => true),
			'region'     => array(AttributeType::String, 'required' => true),
			'container'	 => array(AttributeType::String, 'required' => true),
			'subfolder'  => array(AttributeType::String, 'default' => ''),
		);
	}

	/**
	 * @inheritDoc BaseFlysystemFileSourceType::createAdapter()
	 *
	 * @return RackspaceAdapter
	 */
	protected function createAdapter()
	{
		$client = static::getClient($this->getSettings()->username, $this->getSettings()->apiKey);

		$store = $client->objectStoreService('cloudFiles', $this->getSettings()->region);
		$container = $store->getContainer($this->getSettings()->container);

		return new RackspaceAdapter($container);
	}

	/**
	 * Get the AWS S3 client.
	 *
	 * @param $username
	 * @param $apiKey
	 *
	 * @return OpenStack
	 */
	protected static function getClient($username, $apiKey)
	{
		$config = array('username' => $username, 'apiKey' => $apiKey);

		return new RackspaceClient(RackspaceClient::US_IDENTITY_ENDPOINT, $config);
	}
}
