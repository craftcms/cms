<?php
namespace craft\app\assetsourcetypes;

use Aws\S3\Exception\AccessDeniedException;
use Craft;
use craft\app\enums\AttributeType;
use craft\app\io\flysystemadapters\AwsS3 as AwsS3Adapter;
use \Aws\S3\S3Client as S3Client;

/**
 * The Amazon S3 source type class. Handles the implementation of the AWS S3 service as an asset source type in
 * Craft.
 *
 * @author     Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright  Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license    http://buildwithcraft.com/license Craft License Agreement
 * @see        http://buildwithcraft.com
 * @package    craft.app.assetsourcetypes
 * @since      1.0
 */
class AwsS3 extends BaseAssetSourceType
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
		return Craft::t('app', 'Amazon S3');
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

		return Craft::$app->templates->render('_components/assetsourcetypes/S3/settings', array(
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
			$config = array();
		}
		else
		{
			$config = array(
				'key' => $keyId,
				'secret' => $secret
			);
		}

		$client = static::getClient($config);

		$objects = $client->listBuckets();

		if (empty($objects['Buckets']))
		{
			return array();
		}

		$buckets = $objects['Buckets'];
		$bucketList = array();

		foreach ($buckets as $bucket)
		{
			try
			{
				$location = $client->getBucketLocation(array('Bucket' => $bucket['Name']));
			}
			catch (AccessDeniedException $exception)
			{
				continue;
			}

			$bucketList[] = array(
				'bucket'    => $bucket['Name'],
				'urlPrefix' => 'http://'.$bucket['Name'].'.s3.amazonaws.com/',
				'region'    => isset($location['Location']) ? $location['Location'] : ''
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
			'keyId'      => array(AttributeType::String),
			'secret'     => array(AttributeType::String),
			'bucket'     => array(AttributeType::String, 'required' => true),
			'region'     => array(AttributeType::String),
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
		$keyId = $this->getSettings()->keyId;
		$secret = $this->getSettings()->secret;

		if (empty($keyId) || empty($secret))
		{
			$config = array();
		}
		else
		{
			$config = array(
				'key' => $keyId,
				'secret' => $secret
			);
		}

		$config['region'] = $this->getSettings()->region;

		$client = static::getClient($config);

		return new AwsS3Adapter($client, $this->getSettings()->bucket, $this->getSettings()->subfolder);
	}

	/**
	 * Get the AWS S3 client.
	 *
	 * @param $config
	 *
	 * @return S3Client
	 */
	protected static function getClient($config = array())
	{
		return S3Client::factory($config);
	}
}
