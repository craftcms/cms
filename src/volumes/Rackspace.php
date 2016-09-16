<?php
namespace craft\app\volumes;

use Craft;
use craft\app\base\Volume;
use League\Flysystem\Rackspace\RackspaceAdapter;
use OpenCloud\Identity\Resource\Token;
use \OpenCloud\OpenStack;
use \OpenCloud\Rackspace as RackspaceClient;


/**
 * The Rackspace Cloud volume class. Handles the implementation of the Rackspace Cloud Storage service as a volume type in
 * Craft.
 *
 * @author     Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright  Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license    http://craftcms.com/license Craft License Agreement
 * @see        http://craftcms.com
 * @package    craft.app.volumes
 * @since      3.0
 */
class Rackspace extends Volume
{

    /**
     * Cache key to use for caching purposes
     */
    const CACHE_KEY_PREFIX = 'rackspace.';

    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName()
    {
        return Craft::t('app', 'Rackspace Cloud Files');
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
     * Set to true if the Adapter expects folder names to have trailing slashes
     *
     * @var bool
     */
    protected $foldersHaveTrailingSlashes = false;

    /**
     * Path to the root of this sources local folder.
     *
     * @var string
     */
    public $subfolder = "";
    /**
     * Rackspace username
     *
     * @var string
     */
    public $username = "";

    /**
     * Rackspace API key
     *
     * @var string
     */
    public $apiKey = "";

    /**
     * Container to use
     *
     * @var string
     */
    public $container = "";

    /**
     * Region to use
     *
     * @var string
     */
    public $region = "";

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['username', 'apiKey', 'region', 'container'], 'required'];

        return $rules;
    }

    /**
     * @inheritdoc
     *
     * @return string|null
     */
    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('_components/volumes/Rackspace/settings',
            [
                'volume' => $this
            ]);
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
        if (empty($username) || empty($apiKey) || empty($region)) {
            throw new \InvalidArgumentException('You must specify a username, the API key and a region to get the container list.');
        }

        $client = static::getClient($username, $apiKey);

        $service = $client->objectStoreService('cloudFiles', $region);

        $containerList = $service->getCdnService()->listContainers();

        $returnData = [];

        while ($container = $containerList->next()) {
            $returnData[] = (object)[
                'container' => $container->name,
                'urlPrefix' => rtrim($container->getCdnUri(), '/').'/'
            ];
        }

        return $returnData;
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
     *
     * @return RackspaceAdapter
     */
    protected function createAdapter()
    {
        $client = static::getClient($this->username, $this->apiKey);

        $store = $client->objectStoreService('cloudFiles', $this->region);
        $container = $store->getContainer($this->container);

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
        $config = ['username' => $username, 'apiKey' => $apiKey];

        $client = new RackspaceClient(RackspaceClient::US_IDENTITY_ENDPOINT, $config);

        // Check if we have a cached token
        $tokenKey = static::CACHE_KEY_PREFIX.md5($username.$apiKey);
        if (Craft::$app->cache->exists($tokenKey)) {
            $client->importCredentials(unserialize(Craft::$app->cache->get($tokenKey)));
        }

        /** @var Token $token */
        $token = $client->getTokenObject();

        // If it's not a valid token, re-authenticate and store the token
        if (!$token || ($token && $token->hasExpired())) {
            $client->authenticate();
            $tokenData = $client->exportCredentials();
            Craft::$app->cache->set($tokenKey, serialize($tokenData));
        }

        return $client;
    }
}
