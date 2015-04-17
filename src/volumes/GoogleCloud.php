<?php
/**
 * The Google Cloud source type class. Handles the implementation of the Google Cloud Storage service as an asset source type in
 * Craft.
 *
 * @author     Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright  Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license    http://buildwithcraft.com/license Craft License Agreement
 * @see        http://buildwithcraft.com
 * @package    craft.app.volumes
 * @since      1.0
 */

namespace craft\app\volumes;

use Craft;
use craft\app\base\Volume;
use craft\app\io\flysystemadapters\AwsS3 as AwsS3Adapter;
use \Aws\S3\S3Client as S3Client;

Craft::$app->requireEdition(Craft::Pro);


class GoogleCloud extends Volume
{
	// Static
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public static function displayName()
	{
		return Craft::t('app', 'Google Cloud Storage');
	}

	// Properties
	// =========================================================================

	/**
	 * Whether this is a local source or not. Defaults to false.
	 *
	 * @var bool
	 */
	protected $isSourceLocal = false;

	/**
	 * Path to the root of this sources local folder.
	 *
	 * @var string
	 */
	public $subfolder = "";

	/**
	 * Google Cloud interoperable key ID
	 *
	 * @var string
	 */
	public $keyId = "";

	/**
	 * Google Cloud interoperable key secret
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

	// Public Methods
	// =========================================================================


	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		$rules = parent::rules();
		$rules[] = [['keyId', 'secret', 'bucket'], 'required'];
		return $rules;
	}

	/**
	 * @inheritdoc
	 */
	public function getSettingsHtml()
	{
		return Craft::$app->getView()->renderTemplate('_components/volumes/GoogleCloud/settings', array(
			'volume' => $this,
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
		$client = static::getClient($this->keyId, $this->secret, array('base_url' => 'https://storage.googleapis.com'));

		return new AwsS3Adapter($client, $this->bucket, $this->subfolder);
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
