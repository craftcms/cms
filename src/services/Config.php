<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\services;

use Craft;
use craft\config\DbCacheConfig;
use craft\config\DbConfig;
use craft\config\FileCacheConfig;
use craft\config\GeneralConfig;
use craft\config\MemCacheConfig;
use craft\helpers\ArrayHelper;
use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use yii\base\Component;
use yii\base\ErrorException;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\base\Object;

/**
 * The Config service provides APIs for retrieving the values of Craft’s [config settings](http://craftcms.com/docs/config-settings),
 * as well as the values of any plugins’ config settings.
 *
 * An instance of the Config service is globally accessible in Craft via [[Application::config `Craft::$app->getConfig()`]].
 *
 * @property DbConfig        $db        the DB config settings
 * @property DbCacheConfig   $dbCache   the DB Cache config settings
 * @property FileCacheConfig $fileCache the file cache config settings
 * @property GeneralConfig   $general   the general config settings
 * @property MemCacheConfig  $memCache  the Memcached config settings
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Config extends Component
{
    // Constants
    // =========================================================================

    const CATEGORY_DB = 'db';
    const CATEGORY_DBCACHE = 'dbcache';
    const CATEGORY_FILECACHE = 'filecache';
    const CATEGORY_GENERAL = 'general';
    const CATEGORY_MEMCACHE = 'memcache';

    // Properties
    // =========================================================================

    /**
     * @var string|null The environment ID Craft is currently running in.
     */
    public $env;

    /**
     * @var string The path to the config directory
     */
    public $configDir = '';

    /**
     * @var string The path to the directory containing the default application config settings
     */
    public $appDefaultsDir = '';

    /**
     * @var array
     */
    private $_configSettings = [];

    // Public Methods
    // =========================================================================

    /**
     * Returns all of the config settings for a given category.
     *
     * @param string $category The config category
     *
     * @return Object The config settings
     * @throws InvalidParamException if $category is invalid
     */
    public function getConfigSettings(string $category): Object
    {
        if (isset($this->_configSettings[$category])) {
            return $this->_configSettings[$category];
        }

        switch ($category) {
            case self::CATEGORY_DB:
                $class = DbConfig::class;
                break;
            case self::CATEGORY_DBCACHE:
                $class = DbCacheConfig::class;
                break;
            case self::CATEGORY_FILECACHE:
                $class = FileCacheConfig::class;
                break;
            case self::CATEGORY_GENERAL:
                $class = GeneralConfig::class;
                break;
            case self::CATEGORY_MEMCACHE:
                $class = MemCacheConfig::class;
                break;
            default:
                throw new InvalidParamException('Invalid config category: '.$category);
        }

        // Get any custom config settings
        $config = $this->getConfigFromFile($category);
        $config = $this->_configSettings[$category] = new $class($config);

        // todo: remove this eventually
        if ($category === self::CATEGORY_GENERAL) {
            /** @var GeneralConfig $config */
            if ($config->securityKey === null) {
                $keyPath = Craft::$app->getPath()->getRuntimePath().DIRECTORY_SEPARATOR.'validation.key';
                if (file_exists($keyPath)) {
                    $config->securityKey = trim(file_get_contents($keyPath));
                } else {
                    $key = Craft::$app->getSecurity()->generateRandomString();
                    try {
                        FileHelper::writeToFile($keyPath, $key);
                    } catch (ErrorException $e) {
                        throw new InvalidConfigException('The securityKey config setting is required, and an auto-generated value could not be generated: '.$e->getMessage());
                    }
                    $config->securityKey = $key;
                }
                Craft::$app->getDeprecator()->log('validation.key', "The auto-generated validation key stored at {$keyPath} has been deprecated. Copy its value to the “securityKey” config setting in config/general.php.");
            }
            if ($config->siteUrl === null && defined('CRAFT_SITE_URL')) {
                Craft::$app->getDeprecator()->log('CRAFT_SITE_URL', 'The CRAFT_SITE_URL constant has been deprecated. Set the “siteUrl” config setting in config/general.php instead.');
                $config->siteUrl = CRAFT_SITE_URL;
            }
        }

        return $config;
    }

    /**
     * Returns the DB config settings.
     *
     * @return DbConfig
     */
    public function getDb(): DbConfig
    {
        return $this->getConfigSettings(self::CATEGORY_DB);
    }

    /**
     * Returns the DB cache config settings.
     *
     * @return DbCacheConfig
     */
    public function getDbCache(): DbCacheConfig
    {
        return $this->getConfigSettings(self::CATEGORY_DBCACHE);
    }

    /**
     * Returns the file cache config settings.
     *
     * @return FileCacheConfig
     */
    public function getFileCache(): FileCacheConfig
    {
        return $this->getConfigSettings(self::CATEGORY_FILECACHE);
    }

    /**
     * Returns the general config settings.
     *
     * @return GeneralConfig
     */
    public function getGeneral(): GeneralConfig
    {
        return $this->getConfigSettings(self::CATEGORY_GENERAL);
    }

    /**
     * Returns the Memcached config settings.
     *
     * @return MemCacheConfig
     */
    public function getMemCache(): MemCacheConfig
    {
        return $this->getConfigSettings(self::CATEGORY_MEMCACHE);
    }

    /**
     * Loads a config file from the config/ folder, checks if it's a multi-environment
     * config, and returns the values.
     *
     * @param $filename
     *
     * @return array
     */
    public function getConfigFromFile(string $filename): array
    {
        $path = $this->configDir.DIRECTORY_SEPARATOR.$filename.'.php';

        if (!file_exists($path)) {
            return [];
        }

        if (!is_array($config = @include $path)) {
            return [];
        }

        // If it's not a multi-environment config, return the whole thing
        if (!array_key_exists('*', $config)) {
            return $config;
        }

        // If no environment was specified, just look in the '*' array
        if ($this->env === null) {
            return $config['*'];
        }

        $mergedConfig = [];
        foreach ($config as $env => $envConfig) {
            if ($env === '*' || StringHelper::contains($this->env, $env)) {
                $mergedConfig = ArrayHelper::merge($mergedConfig, $envConfig);
            }
        }

        return $mergedConfig;
    }
}
