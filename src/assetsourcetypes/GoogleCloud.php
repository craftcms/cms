<?php
/**
 * The Google Cloud source type class. Handles the implementation of the Google Cloud Storage service as an asset source type in
 * Craft.
 *
 * @author     Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright  Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license    http://buildwithcraft.com/license Craft License Agreement
 * @see        http://buildwithcraft.com
 * @package    craft.app.assetsourcetypes
 * @since      1.0
 */

namespace craft\app\assetsourcetypes;

use Craft;
use craft\app\enums\AttributeType;
use craft\app\io\flysystemadapters\AwsS3 as AwsS3Adapter;
use \Aws\S3\S3Client as S3Client;

Craft::$app->requireEdition(Craft::Pro);


class GoogleCloud extends BaseAssetSourceType
{
	// Properties
	// =========================================================================

	/**
	 * Whether this is a local source or not. Defaults to false.
	 *
	 * @var bool
	 */
	protected $isSourceLocal = false;


	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc IComponentType::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('app', 'Google Cloud Storage');
	}

	/**
	 * @inheritDoc ISavableComponentType::getSettingsHtml()
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		$settings = $this->getSettings();

		//@TODO add expires settings
		$settings->expires = array('amount' => '', 'period' => '');

		return Craft::$app->templates->render('_components/assetsourcetypes/GoogleCloud/settings', array(
			'settings' => $settings,
			'periods' => array_merge(array('' => ''))
		));
	}

	/**
	 * Get the bucket list using the specified credentials.
	 *
	 * @param $keyId
	 * @param $secret
	 *
	 * @throws \InvalidArgumentException
	 * @return array
	 */
	public static function loadBucketList($keyId, $secret)
	{
		if (empty($keyId) || empty($secret))
		{
			throw new \InvalidArgumentException(Craft::t('app', 'You must specify secret key ID and the secret key to get the bucket list.'));
		}

		$client = static::getClient($keyId, $secret, array('base_url' => 'https://storage.googleapis.com'));
		$objects = $client->listBuckets();
		if (empty($objects['Buckets']))
		{
			return array();
		}

		$buckets = $objects['Buckets'];
		$bucketList = array();

		foreach ($buckets as $bucket)
		{
			$bucketList[] = array(
				'bucket'    => $bucket['Name'],
				'urlPrefix' => 'http://storage.googleapis.com/'.$bucket['Name'].'/'
			);
		}

		return $bucketList;
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
			'keyId'      => array(AttributeType::String, 'required' => true),
			'secret'     => array(AttributeType::String, 'required' => true),
			'bucket'     => array(AttributeType::String, 'required' => true),
			'subfolder'  => array(AttributeType::String, 'default' => ''),
			'expires'    => array(AttributeType::String, 'default' => ''),
		);
	}

	/**
	 * @inheritDoc BaseFlysystemFileSourceType::createAdapter()
	 *
	 * @return AwsS3Adapter
	 */
	protected function createAdapter()
	{
		$client = static::getClient($this->getSettings()->keyId, $this->getSettings()->secret, array('base_url' => 'https://storage.googleapis.com'));

		return new AwsS3Adapter($client, $this->getSettings()->bucket, $this->getSettings()->subfolder);
	}

	/**
	 * Get the Google Cloud client.
	 *
	 * @param $keyId
	 * @param $secret
	 * @param $options
	 *
	 * @return S3Client
	 */
	protected static function getClient($keyId, $secret, $options = array())
	{
		$config = array_merge(array('key' => $keyId, 'secret' => $secret), $options);

		return S3Client::factory($config);
	}
}
