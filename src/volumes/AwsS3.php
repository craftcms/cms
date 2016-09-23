<?php
namespace craft\app\volumes;

use Aws\CloudFront\CloudFrontClient;
use Aws\CloudFront\Exception\CloudFrontException;
use Aws\S3\Exception\AccessDeniedException;
use Craft;
use craft\app\base\Volume;
use craft\app\cache\adapters\GuzzleCacheAdapter;
use craft\app\dates\DateTime;
use craft\app\errors\VolumeException;
use craft\app\helpers\Assets;
use craft\app\helpers\DateTimeHelper;
use craft\app\helpers\StringHelper;
use \League\Flysystem\AwsS3v2\AwsS3Adapter;
use \Aws\S3\S3Client as S3Client;

/**
 * The Amazon S3 Volume. Handles the implementation of the AWS S3 service as a volume in
 * Craft.
 *
 * @author     Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright  Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license    http://craftcms.com/license Craft License Agreement
 * @see        http://craftcms.com
 * @package    craft.app.volumes
 * @since      3.0
 */
class AwsS3 extends Volume
{

    // Constants
    // =========================================================================

    const STORAGE_STANDARD = "STANDARD";
    const STORAGE_REDUCED_REDUNDANCY = "REDUCED_REDUNDANCY";
    const STORAGE_STANDARD_IA = "STANDARD_IA";

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
     * Cache expiration period.
     *
     * @var string
     */
    public $expires = "";

    /**
     * S3 storage class to use.
     *
     * @var string
     */
    public $storageClass = "";

    /**
     * CloudFront Distribution ID
     */
    public $cfDistributionId;

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
        return Craft::$app->getView()->renderTemplate('_components/volumes/AwsS3/settings',
            [
                'volume' => $this,
                'periods' => array_merge(['' => ''], Assets::getPeriodList()),
                'storageClasses' => static::getStorageClasses(),
            ]);
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
        if (empty($keyId) || empty($secret)) {
            $config = [];
        } else {
            $config = [
                'key' => $keyId,
                'secret' => $secret
            ];
        }

        $client = static::getClient($config);

        $objects = $client->listBuckets();

        if (empty($objects['Buckets'])) {
            return [];
        }

        $buckets = $objects['Buckets'];
        $bucketList = [];

        foreach ($buckets as $bucket) {
            try {
                $location = $client->getBucketLocation(['Bucket' => $bucket['Name']]);
            } catch (AccessDeniedException $exception) {
                continue;
            }

            $bucketList[] = [
                'bucket' => $bucket['Name'],
                'urlPrefix' => 'http://'.$bucket['Name'].'.s3.amazonaws.com/',
                'region' => isset($location['Location']) ? $location['Location'] : ''
            ];
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

    /**
     * Return a list of available storage classes.
     *
     * @return array
     */
    public static function getStorageClasses()
    {
        return [
            static::STORAGE_STANDARD => 'Standard',
            static::STORAGE_REDUCED_REDUNDANCY => 'Reduced Redundancy Storage',
            static::STORAGE_STANDARD_IA => 'Infrequent Access Storage'
        ];
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @return AwsS3Adapter
     */
    protected function createAdapter()
    {
        $config = $this->_getConfigArray();

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
    protected static function getClient($config = [])
    {
        $config['credentials.cache'] = static::_getCredentialsCacheAdapter();

        return S3Client::factory($config);
    }

    /**
     * @inheritdoc
     */
    protected function addFileMetadataToConfig($config)
    {
        if (!empty($this->expires) && DateTimeHelper::isValidIntervalString($this->expires)) {
            $expires = new DateTime();
            $now = new DateTime();
            $expires->modify('+'.$this->expires);
            $diff = $expires->format('U') - $now->format('U');
            $config['CacheControl'] = 'max-age='.$diff.', must-revalidate';
        }

        if (!empty($this->storageClass)) {
            $config['StorageClass'] = $this->storageClass;
        }

        return parent::addFileMetadataToConfig($config);
    }

    /**
     * @inheritdoc
     */
    protected function invalidateCdnPath($path)
    {
        if (!empty($this->cfDistributionId)) {
            // If there's a CloudFront distribution ID set, invalidate the path.
            $cfClient = $this->_getCloudFrontClient();

            try {
                $cfClient->createInvalidation(
                    [
                        'DistributionId' => $this->cfDistributionId,
                        'Paths' =>
                            [
                                'Quantity' => 1,
                                'Items' => ['/'.ltrim($path, '/')]
                            ],
                        'CallerReference' => 'Craft-'.StringHelper::randomString(24)
                    ]
                );
            } catch (CloudFrontException $exception) {
                Craft::warning($exception->getMessage());
                throw new VolumeException('Failed to invalidate the CDN path for '.$path);
            }
        }

        return true;
    }

    // Private Methods
    // =========================================================================

    /**
     * Get the credentials cache adapter.
     *
     * @return GuzzleCacheAdapter
     */
    private static function _getCredentialsCacheAdapter()
    {
        if (empty(static::$_cacheAdapter)) {
            static::$_cacheAdapter = new GuzzleCacheAdapter();
        }

        return static::$_cacheAdapter;
    }

    /**
     * Get a CloudFront client.
     *
     * @return CloudFrontClient
     */
    private function _getCloudFrontClient()
    {
        $config = $this->_getConfigArray();

        return CloudFrontClient::factory($config);
    }

    /**
     * Get the config array for AWS Clients.
     *
     * @return array
     */
    private function _getConfigArray()
    {
        $keyId = $this->keyId;
        $secret = $this->secret;

        if (empty($keyId) || empty($secret)) {
            $config = [];
        } else {
            $config = [
                'key' => $keyId,
                'secret' => $secret
            ];
        }

        $config['region'] = $this->region;

        return $config;
    }
}
