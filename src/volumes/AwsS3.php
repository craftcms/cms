<?php
namespace craft\app\volumes;

use Aws\S3\Exception\AccessDeniedException;
use Craft;
use craft\app\base\Volume;
use craft\app\cache\adapters\GuzzleCacheAdapter;
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
 * @package    craft.app.volumes
 * @since      1.0
 */
class AwsS3 extends Volume
{
	// Static
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public static function displayName()
	{
		return Craft::t('app', 'Amazon S3');
	}

	// Properties
	// =========================================================================

	/**
	 * Whether this is a local source or not. Defaults to false.
	 *
	 * @var bool
	 */
	protected $isVolumeLocal = false;

	/**
	 * Subfolder to use
	 *
	 * @var string
	 */
	public $subfolder = "";

	/**
	 * AWS key ID
	 *
	 * @var string
	 */
	public $keyId = "";

	/**
	 * AWS key secret
	 *
	 * @var string
	 */
	public $secret = "";

	/**
	 * Bucket to use
	 *
	 * @var string
	 */
	public $bucket = "";

	/**
	 * Region to use
	 *
	 * @var string
	 */
	public $region = "";

	/**
	 * Cache adapter
	 *
	 * @var GuzzleCacheAdapter
	 */
	private static $_cacheAdapter = null;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		$rules = parent::rules();
		$rules[] = [['bucket', 'region'], 'required'];
		return $rules;
	}

	/**
	 * @inheritdoc
	 */
	public function getSettingsHtml()
	{
		return Craft::$app->getView()->renderTemplate('_components/volumes/AwsS3/settings', array(
			'volume' => $this
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

	/**
	 * @inheritdoc
	 */
	public function getRootPath()
	{
		return null;
	}

	/**
	 * @inheritdoc
	 */
	public function getRootUrl()
	{
		return rtrim(rtrim($this->url, '/').'/'.$this->subfolder, '/').'/';
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 * @return AwsS3Adapter
	 */
	protected function createAdapter()
	{
		$keyId = $this->keyId;
		$secret = $this->secret;

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

		$config['region'] = $this->region;

		$client = static::getClient($config);

		return new AwsS3Adapter($client, $this->bucket, $this->subfolder);
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
		$config['credentials.cache'] = static::_getCredentialsCacheAdapter();

		return S3Client::factory($config);
	}

	/**
	 * Get the credentials cache adapter.
	 *
	 * @return GuzzleCacheAdapter
	 */
	private static function _getCredentialsCacheAdapter()
	{
		if (empty(static::$_cacheAdapter))
		{
			static::$_cacheAdapter = new GuzzleCacheAdapter();
		}

		return static::$_cacheAdapter;
	}
}
