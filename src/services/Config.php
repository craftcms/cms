<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\services;

use Craft;
use craft\base\Plugin;
use craft\config\ApcConfig;
use craft\config\DbCacheConfig;
use craft\config\DbConfig;
use craft\config\FileCacheConfig;
use craft\config\GeneralConfig;
use craft\config\MemCacheConfig;
use craft\db\Connection;
use craft\elements\User;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\ConfigHelper;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\base\Object;

/**
 * The Config service provides APIs for retrieving the values of Craft’s [config settings](http://craftcms.com/docs/config-settings),
 * as well as the values of any plugins’ config settings.
 *
 * An instance of the Config service is globally accessible in Craft via [[Application::config `Craft::$app->getConfig()`]].
 *
 * @property bool           $omitScriptNameInUrls Whether generated URLs should omit “index.php”
 * @property int            $cacheDuration
 * @property bool           $useFileLocks
 * @property int            $dbPort
 * @property string         $cpSetPasswordPath
 * @property bool|int       $elevatedSessionDuration
 * @property string         $loginPath
 * @property string         $dbTablePrefix
 * @property string         $cpLogoutPath
 * @property string         $logoutPath
 * @property string         $cpLoginPath
 * @property string         $resourceTrigger
 * @property array|string[] $allowedFileExtensions
 * @property bool           $usePathInfo          Whether generated URLs should be formatted using PATH_INFO
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Config extends Component
{
    // Constants
    // =========================================================================

    const CATEGORY_APC = 'apc';
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
     * @var int|null
     */
    private $_cacheDuration;

    /**
     * @var bool|null
     */
    private $_omitScriptNameInUrls;

    /**
     * @var bool|null
     */
    private $_usePathInfo;

    /**
     * @var bool|null
     */
    private $_useFileLocks;

    /**
     * @var string[]|null
     */
    private $_allowedFileExtensions;

    /**
     * @var array
     */
    private $_configSettings = [];

    // Public Methods
    // =========================================================================

    /**
     * Returns a localized config setting value by its name.
     *
     * Internally, [[get()]] will be called to get the value of the config setting. If the value is an array,
     * then only a single value in that array will be returned: the one that has a key matching the `$siteId` argument.
     * If no matching key is found, the first element of the array will be returned instead.
     *
     * This function is used for Craft’s “localizable” config settings:
     *
     * - [siteUrl](http://craftcms.com/docs/config-settings#siteUrl)
     * - [invalidUserTokenPath](http://craftcms.com/docs/config-settings#invalidUserTokenPath)
     * - [loginPath](http://craftcms.com/docs/config-settings#loginPath)
     * - [logoutPath](http://craftcms.com/docs/config-settings#logoutPath)
     * - [setPasswordPath](http://craftcms.com/docs/config-settings#setPasswordPath)
     * - [setPasswordSuccessPath](http://craftcms.com/docs/config-settings#setPasswordSuccessPath)
     *
     * @param string      $item       The name of the config setting.
     * @param string|null $siteHandle The site handle to return. Defaults to the current site.
     * @param string      $category   The name of the config file (sans .php). Defaults to 'general'.
     *
     * @return mixed The value of the config setting, or `null` if a value could not be found.
     */
    public function getLocalized(string $item, string $siteHandle = null, string $category = self::CATEGORY_GENERAL)
    {
        $value = $this->getConfigSettings($category)->$item;

        if (!is_array($value)) {
            return $value;
        }

        if (empty($value)) {
            return null;
        }

        if ($siteHandle === null) {
            $siteHandle = Craft::$app->getSites()->currentSite->handle;
        }

        if (isset($value[$siteHandle])) {
            return $value[$siteHandle];
        }

        // Just return the first value
        return reset($value);
    }

    /**
     * Returns all of the config settings for a given category.
     *
     * @param string $category The config category
     *
     * @return Object The config settings
     * @throws InvalidConfigException if $category is invalid
     */
    public function getConfigSettings(string $category): Object
    {
        if (isset($this->_configSettings[$category])) {
            return $this->_configSettings[$category];
        }

        switch ($category) {
            case self::CATEGORY_APC:
                $class = ApcConfig::class;
                break;
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
                throw new InvalidConfigException('Invalid config category: '.$category);
        }

        // Get any custom config settings
        $config = $this->getConfigFromFile($category);

        return $this->_configSettings[$category] = new $class($config);
    }

    /**
     * Returns the APC config settings.
     *
     * @return ApcConfig
     */
    public function getApc(): ApcConfig
    {
        return $this->getConfigSettings(self::CATEGORY_APC);
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
     * Returns the MemCache config settings.
     *
     * @return MemCacheConfig
     */
    public function getMemCache(): MemCacheConfig
    {
        return $this->getConfigSettings(self::CATEGORY_MEMCACHE);
    }

    /**
     * Returns the value of the [cacheDuration](http://craftcms.com/docs/config-settings#cacheDuration) config setting,
     * normalized into seconds.
     *
     * ```php
     * $this->get('cacheDuration'); // 'P1D'
     * $this->getCacheDuration();   // 86400
     * ```
     *
     * @return int The cacheDuration config setting value, in seconds.
     */
    public function getCacheDuration(): int
    {
        if ($this->_cacheDuration !== null) {
            return $this->_cacheDuration;
        }

        return $this->_cacheDuration = ConfigHelper::durationInSeconds($this->getGeneral()->cacheDuration);
    }

    /**
     * Returns whether generated URLs should omit “index.php”, taking the
     * [omitScriptNameInUrls](http://craftcms.com/docs/config-settings#omitScriptNameInUrls) config setting value
     * into account.
     *
     * If the omitScriptNameInUrls config setting is set to `true` or `false`, then its value will be returned directly.
     * Otherwise, `omitScriptNameInUrls()` will try to determine whether the server is set up to support index.php
     * redirection. (See [this help article](http://craftcms.com/help/remove-index.php) for instructions.)
     *
     * It does that by creating a dummy request to the site URL with the URI “/testScriptNameRedirect”. If the index.php
     * redirect is in place, that request should be sent to Craft’s index.php file, which will detect that this is an
     * index.php redirect-testing request, and simply return “success”. If anything besides “success” is returned
     * (i.e. an Apache-styled 404 error), then Craft assumes the index.php redirect is not in fact in place.
     *
     * Results of the redirect test request will be cached for the amount of time specified by the
     * [cacheDuration](http://craftcms.com/docs/config-settings#cacheDuration) config setting.
     *
     * @return bool Whether generated URLs should omit “index.php”.
     */
    public function getOmitScriptNameInUrls(): bool
    {
        if ($this->_omitScriptNameInUrls !== null) {
            return $this->_omitScriptNameInUrls;
        }

        // Check if the config value has actually been set to true/false
        if (is_bool($configVal = $this->getGeneral()->omitScriptNameInUrls)) {
            return $this->_omitScriptNameInUrls = $configVal;
        }

        // PHP Dev Server does omit the script name from 404s without any help from a redirect script,
        // *unless* the URI looks like a file, in which case it'll just throw a 404.
        if (App::isPhpDevServer()) {
            return $this->_omitScriptNameInUrls = false;
        }

        // Check if it's cached
        if (($cachedVal = Craft::$app->getCache()->get('omitScriptNameInUrls')) !== false) {
            return $this->_omitScriptNameInUrls = ($cachedVal === 'y');
        }

        // Cache it early so the testScriptNameRedirect request isn't checking for it too
        Craft::$app->getCache()->set('omitScriptNameInUrls', 'n');

        // Test the server for it
        $this->_omitScriptNameInUrls = false;

        try {
            $baseUrl = Craft::$app->getRequest()->getHostInfo().Craft::$app->getRequest()->getScriptUrl();
            $url = mb_substr($baseUrl, 0, mb_strrpos($baseUrl, '/')).'/testScriptNameRedirect';

            $response = (new Client())->get($url, ['connect_timeout' => 2, 'timeout' => 4]);

            if ((string)$response->getBody() === 'success') {
                $this->_omitScriptNameInUrls = true;
            }
        } catch (RequestException $e) {
            Craft::error('Unable to determine if a script name redirect is in place on the server: '.$e->getMessage(), __METHOD__);
        }

        // Cache it
        Craft::$app->getCache()->set('omitScriptNameInUrls', $this->_omitScriptNameInUrls ? 'y' : 'n');

        return $this->_omitScriptNameInUrls;
    }

    /**
     * Returns whether generated URLs should be formatted using PATH_INFO, taking the
     * [usePathInfo](http://craftcms.com/docs/config-settings#usePathInfo) config setting value into account.
     *
     * This method will usually only be called in the event that [[omitScriptNameInUrls()]] returns `false`
     * (so “index.php” _should_ be included), and it determines what follows “index.php” in the URL.
     *
     * If it returns `true`, a forward slash will be used, making “index.php” look like just another segment of the URL
     * (e.g. http://example.com/index.php/some/path). Otherwise the Craft path will be included in the URL as a query
     * string param named ‘p’ (e.g. http://example.com/index.php?p=some/path).
     *
     * If the usePathInfo config setting is set to `true` or `false`, then its value will be returned directly.
     * Otherwise, `usePathInfo()` will try to determine whether the server is set up to support PATH_INFO.
     * (See http://craftcms.com/help/enable-path-info for instructions.)
     *
     * It does that by creating a dummy request to the site URL with the URI “/index.php/testPathInfo”. If the server
     * supports it, that request should be sent to Craft’s index.php file, which will detect that this is an
     * PATH_INFO-testing request, and simply return “success”. If anything besides “success” is returned
     * (i.e. an Apache-styled 404 error), then Craft assumes the server is not set up to support PATH_INFO.
     *
     * Results of the PATH_INFO test request will be cached for the amount of time specified by the
     * [cacheDuration](http://craftcms.com/docs/config-settings#cacheDuration) config setting.
     *
     * @return bool Whether generated URLs should be formatted using PATH_INFO
     */
    public function getUsePathInfo(): bool
    {
        if ($this->_usePathInfo !== null) {
            return $this->_usePathInfo;
        }

        // Check if the config value has actually been set to true/false
        if (is_bool($configVal = $this->getGeneral()->usePathInfo)) {
            return $this->_usePathInfo = $configVal;
        }

        // If there is already a PATH_INFO var available, we know it supports it.
        if (!empty($_SERVER['PATH_INFO'])) {
            return $this->_usePathInfo = true;
        }

        // PHP Dev Server supports path info, and doesn't support simultaneous
        //requests, so we need to explicitly check for that.
        if (App::isPhpDevServer()) {
            return $this->_usePathInfo = true;
        }

        // Check if it's cached
        if (($cachedVal = Craft::$app->getCache()->get('usePathInfo')) !== false) {
            return $this->_usePathInfo = ($cachedVal === 'y');
        }

        // Cache it early so the testPathInfo request isn't checking for it too
        Craft::$app->getCache()->set('usePathInfo', 'n');

        // Test the server for it
        $this->_usePathInfo = false;

        try {
            $url = Craft::$app->getRequest()->getHostInfo().Craft::$app->getRequest()->getScriptUrl().'/testPathInfo';
            $response = (new Client())->get($url, ['connect_timeout' => 2, 'timeout' => 4]);

            if ((string)$response->getBody() === 'success') {
                $this->_usePathInfo = true;
            }
        } catch (RequestException $e) {
            Craft::error('Unable to determine if PATH_INFO is enabled on the server: '.$e->getMessage(), __METHOD__);
        }

        // Cache it
        Craft::$app->getCache()->set('usePathInfo', $this->_usePathInfo ? 'y' : 'n');

        return $this->_usePathInfo;
    }

    /**
     * Sets PHP’s memory limit to the maximum specified by the
     * [phpMaxMemoryLimit](http://craftcms.com/docs/config-settings#phpMaxMemoryLimit) config setting, and gives
     * the script an unlimited amount of time to execute.
     *
     * @return void
     */
    public function maxPowerCaptain()
    {
        if ($this->getGeneral()->phpMaxMemoryLimit !== '') {
            @ini_set('memory_limit', $this->getGeneral()->phpMaxMemoryLimit);
        } else {
            // Grab. It. All.
            @ini_set('memory_limit', -1);
        }

        // I need more time.
        @set_time_limit(0);
    }

    /**
     * Returns the configured user session duration in seconds, or `null` if there is none because user sessions should
     * expire when the HTTP session expires.
     *
     * You can choose whether the
     * [rememberedUserSessionDuration](http://craftcms.com/docs/config-settings#rememberedUserSessionDuration)
     * or [userSessionDuration](http://craftcms.com/docs/config-settings#userSessionDuration) config setting
     * should be used with the $remembered param. If rememberedUserSessionDuration’s value is empty (disabling the
     * feature) then userSessionDuration will be used regardless of $remembered.
     *
     * @param bool $remembered    Whether the rememberedUserSessionDuration config setting should be used if it’s set.
     *                            Default is `false`.
     *
     * @return int|null The user session duration in seconds, or `null` if user sessions should expire along with the
     *                  HTTP session.
     */
    public function getUserSessionDuration(bool $remembered = false)
    {
        if ($remembered) {
            $duration = $this->getGeneral()->rememberedUserSessionDuration;
        }

        // Even if $remembered = true, it's possible that they've disabled long-term user sessions
        // by setting rememberedUserSessionDuration = 0
        if (empty($duration)) {
            $duration = $this->getGeneral()->userSessionDuration;
        }

        if ($duration) {
            return ConfigHelper::durationInSeconds($duration);
        }

        return null;
    }

    /**
     * Returns the configured elevated session duration in seconds.
     *
     * @return int|bool The elevated session duration in seconds or false if it has been disabled.
     */
    public function getElevatedSessionDuration()
    {
        $seconds = ConfigHelper::durationInSeconds($this->getGeneral()->elevatedSessionDuration);

        // See if it has been disabled.
        if ($seconds === 0) {
            return false;
        }

        return $seconds;
    }

    /**
     * Returns the user login path based on the type of the current request.
     *
     * If it’s a front-end request, the [loginPath](http://craftcms.com/docs/config-settings#loginPath) config
     * setting value will be returned. Otherwise the path specified in [[getCpLoginPath()]] will be returned.
     *
     * @return string The login path.
     */
    public function getLoginPath(): string
    {
        $request = Craft::$app->getRequest();

        if ($request->getIsConsoleRequest() || $request->getIsSiteRequest()) {
            return $this->getLocalized('loginPath');
        }

        return $this->getCpLoginPath();
    }

    /**
     * Returns the user logout path based on the type of the current request.
     *
     * If it’s a front-end request, the [logoutPath](http://craftcms.com/docs/config-settings#logoutPath) config
     * setting value will be returned. Otherwise the path specified in [[getCpLogoutPath()]] will be returned.
     *
     * @return string The logout path.
     */
    public function getLogoutPath(): string
    {
        $request = Craft::$app->getRequest();

        if ($request->getIsConsoleRequest() || $request->getIsSiteRequest()) {
            return $this->getLocalized('logoutPath');
        }

        return $this->getCpLogoutPath();
    }

    /**
     * Returns a user’s Set Password path with a given activation code and user’s UID.
     *
     * @param string $code The activation code.
     * @param string $uid  The user’s UID.
     * @param User   $user The user.
     * @param bool   $full Whether a full URL should be returned. Defaults to `false`.
     *
     * @return string The Set Password path.
     *
     * @internal This is a little awkward in that the method is called getActivateAccount**Path**, but it's also capable
     * of returning a full **URL**. And it requires you pass in both a user’s UID *and* the User - presumably we
     * could get away with just the User and get the UID from that.
     *
     * @todo     Create a new getSetPasswordUrl() method (probably elsewhere, such as Url) which handles
     * everything that setting $full to `true` currently does here. The function should not accetp a UID since that's
     * already covered by the User. Let this function continue working as a wrapper for getSetPasswordUrl() for the
     * time being, with deprecation logs.
     */
    public function getSetPasswordPath(string $code, string $uid, User $user, bool $full = false): string
    {
        if ($user->can('accessCp')) {
            $url = $this->getCpSetPasswordPath();

            if ($full) {
                if (Craft::$app->getRequest()->getIsSecureConnection()) {
                    $url = UrlHelper::cpUrl($url, [
                        'code' => $code,
                        'id' => $uid
                    ], 'https');
                } else {
                    $url = UrlHelper::cpUrl($url, [
                        'code' => $code,
                        'id' => $uid
                    ]);
                }
            }
        } else {
            $url = $this->getLocalized('setPasswordPath');

            if ($full) {
                if (Craft::$app->getRequest()->getIsSecureConnection()) {
                    $url = UrlHelper::url($url, [
                        'code' => $code,
                        'id' => $uid
                    ], 'https');
                } else {
                    $url = UrlHelper::url($url, [
                        'code' => $code,
                        'id' => $uid
                    ]);
                }
            }
        }

        return $url;
    }

    /**
     * Returns the path to the CP’s Set Password page.
     *
     * @return string The Set Password path.
     */
    public function getCpSetPasswordPath(): string
    {
        return 'setpassword';
    }

    /**
     * Returns the path to the CP’s Login page.
     *
     * @return string The Login path.
     */
    public function getCpLoginPath(): string
    {
        return 'login';
    }

    /**
     * Returns the path to the CP’s Logout page.
     *
     * @return string The Logout path.
     */
    public function getCpLogoutPath(): string
    {
        return 'logout';
    }

    /**
     * Returns the Resource Request trigger word based on the type of the current request.
     *
     * If it’s a front-end request, the [resourceTrigger](http://craftcms.com/docs/config-settings#resourceTrigger)
     * config setting value will be returned. Otherwise `'resources'` will be returned.
     *
     * @return string The Resource Request trigger word.
     */
    public function getResourceTrigger(): string
    {
        $request = Craft::$app->getRequest();

        if (!$request->getIsConsoleRequest() && $request->getIsCpRequest()) {
            return 'resources';
        }

        return $this->getGeneral()->resourceTrigger;
    }

    /**
     * Returns whether the system is allowed to be auto-updated to the latest release.
     *
     * @return bool Whether the system is allowed to be auto-updated to the latest release.
     */
    public function allowAutoUpdates(): bool
    {
        $update = Craft::$app->getUpdates()->getUpdates();

        if (!$update) {
            return false;
        }

        $configVal = $this->getGeneral()->allowAutoUpdates;

        if (is_bool($configVal)) {
            return $configVal;
        }

        if ($configVal === 'patch-only') {
            // Return true if the major and minor versions are still the same
            return (App::majorMinorVersion($update->app->latestVersion) === App::majorMinorVersion(Craft::$app->version));
        }

        if ($configVal === 'minor-only') {
            // Return true if the major version is still the same
            return (App::majorVersion($update->app->latestVersion) === App::majorVersion(Craft::$app->version));
        }

        return false;
    }

    /**
     * Returns whether to use file locks when writing to files.
     *
     * @return bool
     */
    public function getUseFileLocks(): bool
    {
        if ($this->_useFileLocks !== null) {
            return $this->_useFileLocks;
        }

        if (is_bool($configVal = $this->getGeneral()->useFileLocks)) {
            return $this->_useFileLocks = $configVal;
        }

        // Do we have it cached?
        if (($cachedVal = Craft::$app->getCache()->get('useFileLocks')) !== false) {
            return $this->_useFileLocks = ($cachedVal === 'y');
        }

        // Try a test lock
        $this->_useFileLocks = false;

        try {
            $mutex = Craft::$app->getMutex();
            $name = uniqid('test_lock', true);
            if (!$mutex->acquire($name)) {
                throw new Exception('Unable to acquire test lock.');
            }
            if (!$mutex->release($name)) {
                throw new Exception('Unable to release test lock.');
            }
            $this->_useFileLocks = true;
        } catch (\Exception $e) {
            Craft::warning('Write lock test failed: '.$e->getMessage(), __METHOD__);
        }

        // Cache for two months
        $cachedValue = $this->_useFileLocks ? 'y' : 'n';
        Craft::$app->getCache()->set('useFileLocks', $cachedValue, 5184000);

        return $this->_useFileLocks;
    }

    /**
     * Returns an array of allowed file extensions.
     *
     * @return string[] The allowed file extensions
     */
    public function getAllowedFileExtensions(): array
    {
        if ($this->_allowedFileExtensions !== null) {
            return $this->_allowedFileExtensions;
        }

        $this->_allowedFileExtensions = ArrayHelper::toArray($this->getGeneral()->allowedFileExtensions);
        $extra = $this->getGeneral()->extraAllowedFileExtensions;

        if (!empty($extra)) {
            $extra = ArrayHelper::toArray($extra);
            $this->_allowedFileExtensions = array_merge($this->_allowedFileExtensions, $extra);
        }

        $this->_allowedFileExtensions = array_map('strtolower', $this->_allowedFileExtensions);

        return $this->_allowedFileExtensions;
    }

    /**
     * Returns whether a given extension is allowed to be uploaded, per the
     * allowedFileExtensions and extraAllowedFileExtensions config settings.
     *
     * @param string $extension The extension in question
     *
     * @return bool Whether the extension is allowed
     */
    public function isExtensionAllowed(string $extension): bool
    {
        return in_array(strtolower($extension), $this->getAllowedFileExtensions(), true);
    }

    /**
     * Returns the application’s configured DB table prefix.
     *
     * @return string
     */
    public function getDbTablePrefix(): string
    {
        // Table prefixes cannot be longer than 5 characters
        $tablePrefix = rtrim($this->getDb()->tablePrefix, '_');

        if ($tablePrefix) {
            if (StringHelper::length($tablePrefix) > 5) {
                $tablePrefix = substr($tablePrefix, 0, 5);
            }

            $tablePrefix .= '_';
        } else {
            $tablePrefix = '';
        }

        return $tablePrefix;
    }

    /**
     * If a custom database port has been set in config/db.php, will return that value.
     * Otherwise, will return the default port depending on the database type that is
     * selected.
     *
     * @return int
     */
    public function getDbPort(): int
    {
        $port = $this->getDb()->port;
        $driver = $this->getDb()->driver;

        if ($port === null || $port === '') {
            switch ($driver) {
                case Connection::DRIVER_MYSQL:
                    $port = 3306;
                    break;
                case Connection::DRIVER_PGSQL:
                    $port = 5432;
                    break;
            }
        }

        return $port;
    }

    /**
     * Loads a config file from the config/ folder, checks if it's a multi-environment
     * config, and returns the values.
     *
     * @param $filename
     *
     * @return array
     */
    public function getConfigFromFile(string $filename)
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
