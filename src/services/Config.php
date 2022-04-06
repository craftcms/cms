<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\config\DbConfig;
use craft\config\GeneralConfig;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use yii\base\Component;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;

/**
 * The Config service provides APIs for retrieving the values of Craft’s [config settings](http://craftcms.com/docs/config-settings),
 * as well as the values of any plugins’ config settings.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getConfig()|`Craft::$app->config`]].
 *
 * @property-read DbConfig $db the DB config settings
 * @property-read GeneralConfig $general the general config settings
 * @property-read object $custom the custom config settings
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Config extends Component
{
    /**
     * @since 4.0.0
     */
    public const CATEGORY_CUSTOM = 'custom';
    public const CATEGORY_DB = 'db';
    public const CATEGORY_GENERAL = 'general';

    /**
     * @var string|null The environment ID Craft is currently running in.
     *
     * ---
     * ```php
     * $env = Craft::$app->config->env;
     * ```
     * ```twig
     * {% if craft.app.config.env == 'production' %}
     *   {% include "_includes/ga" %}
     * {% endif %}
     * ```
     */
    public ?string $env = null;

    /**
     * @var string The path to the config directory
     */
    public string $configDir = '';

    /**
     * @var string The path to the directory containing the default application config settings
     */
    public string $appDefaultsDir = '';

    /**
     * @var array
     */
    private array $_configSettings = [];

    /**
     * @var string|null
     */
    private ?string $_dotEnvPath = null;

    /**
     * Returns all of the config settings for a given category.
     *
     * @param string $category The config category
     * @return object The config settings
     * @throws InvalidArgumentException if $category is invalid
     * @throws InvalidConfigException if the securityKey general config setting is not set, and a auto-generated one could not be saved
     */
    public function getConfigSettings(string $category): object
    {
        if (!isset($this->_configSettings[$category])) {
            $this->_configSettings[$category] = $this->_createConfigObj($category);

            // This needs to happen here – after `$this->_configSettings[$category]` has been set – to avoid
            // an infinite recursion bug
            if ($category === self::CATEGORY_GENERAL && !isset($this->_configSettings[$category]->securityKey)) {
                $keyPath = Craft::$app->getPath()->getRuntimePath() . DIRECTORY_SEPARATOR . 'validation.key';
                if (file_exists($keyPath)) {
                    $this->_configSettings[$category]->securityKey = trim(file_get_contents($keyPath));
                } else {
                    $key = Craft::$app->getSecurity()->generateRandomString();
                    try {
                        FileHelper::writeToFile($keyPath, $key);
                    } catch (ErrorException $e) {
                        throw new InvalidConfigException('The securityKey config setting is required, and an auto-generated value could not be generated: ' . $e->getMessage());
                    }
                    $this->_configSettings[$category]->securityKey = $key;
                }
                Craft::$app->getDeprecator()->log('validation.key', "The auto-generated validation key stored at `$keyPath` has been deprecated. Copy its value to the `securityKey` config setting in `config/general.php`.");
            }
        }

        return $this->_configSettings[$category];
    }

    /**
     * Creates a new config object.
     *
     * @param string $category The config category
     * @return object
     */
    private function _createConfigObj(string $category): object
    {
        $config = $this->getConfigFromFile($category);

        return match ($category) {
            self::CATEGORY_CUSTOM => (object)$config,
            self::CATEGORY_DB => new DbConfig($config),
            self::CATEGORY_GENERAL => new GeneralConfig($config),
            default => throw new InvalidArgumentException("Invalid config category: $category"),
        };
    }

    /**
     * Returns the custom config settings.
     *
     * ---
     *
     * ```php
     * $myCustomSetting = Craft::$app->config->custom->myCustomSetting;
     * ```
     * ```twig
     * {% set myCustomSetting = craft.app.config.custom.myCustomSetting %}
     * ```
     *
     * @return object
     * @since 4.0.0
     */
    public function getCustom(): object
    {
        return $this->getConfigSettings(self::CATEGORY_CUSTOM);
    }

    /**
     * Returns the DB config settings.
     *
     * ---
     *
     * ```php
     * $username = Craft::$app->config->db->username;
     * ```
     * ```twig
     * {% set username = craft.app.config.db.username %}
     * ```
     *
     * @return DbConfig
     */
    public function getDb(): DbConfig
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getConfigSettings(self::CATEGORY_DB);
    }

    /**
     * Returns the general config settings.
     *
     * ---
     *
     * ```php
     * $logoutPath = Craft::$app->config->general->logoutPath;
     * ```
     * ```twig
     * <a href="{{ url(craft.app.config.general.logoutPath) }}">
     *   Logout
     * </a>
     * ```
     *
     * @return GeneralConfig
     */
    public function getGeneral(): GeneralConfig
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getConfigSettings(self::CATEGORY_GENERAL);
    }

    /**
     * Returns the path to a config file.
     *
     * @param string $filename The filename (sans .php extension)
     * @return string
     */
    public function getConfigFilePath(string $filename): string
    {
        return $this->configDir . DIRECTORY_SEPARATOR . $filename . '.php';
    }

    /**
     * Loads a config file from the config/ folder, checks if it's a multi-environment
     * config, and returns the values.
     *
     * ---
     *
     * ```php
     * // get the values defined in config/foo.php
     * $settings = Craft::$app->config->getConfigFromFile('foo');
     * ```
     *
     * @param string $filename
     * @return array
     */
    public function getConfigFromFile(string $filename): array
    {
        $path = $this->getConfigFilePath($filename);

        if (!file_exists($path)) {
            return [];
        }

        if (!is_array($config = @include $path)) {
            return [];
        }

        // If it’s not a multi-environment config, return the whole thing
        if (!array_key_exists('*', $config)) {
            return $config;
        }

        // If no environment was specified, just look in the '*' array
        if (!isset($this->env)) {
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

    /**
     * Returns the path to the .env file (regardless of whether it exists).
     *
     * @return string
     */
    public function getDotEnvPath(): string
    {
        return $this->_dotEnvPath ?? ($this->_dotEnvPath = Craft::getAlias('@root/.env'));
    }

    /**
     * Sets an environment variable value in the project's .env file.
     *
     * @param string $name The environment variable name
     * @param string $value The environment variable value
     * @throws Exception if the .env file doesn't exist
     */
    public function setDotEnvVar(string $name, string $value): void
    {
        $path = $this->getDotEnvPath();

        if (!file_exists($path)) {
            throw new Exception("No .env file exists at $path");
        }

        $contents = file_get_contents($path);
        $qName = preg_quote($name, '/');
        $slashedValue = addslashes($value);

        // Only surround with quotes if the value contains a space
        if (str_contains($slashedValue, ' ') || str_contains($slashedValue, '#')) {
            $slashedValue = "\"$slashedValue\"";
        }

        $def = "$name=$slashedValue";
        $token = StringHelper::randomString();
        $contents = preg_replace("/^(\s*)$qName=.*/m", $token, $contents, -1, $count);

        if ($count !== 0) {
            $contents = str_replace($token, $def, $contents);
        } else {
            $contents = rtrim($contents);
            $contents = ($contents ? $contents . PHP_EOL . PHP_EOL : '') . $def . PHP_EOL;
        }

        FileHelper::writeToFile($path, $contents);

        // Now actually set the environment variable
        putenv("$name=$value");
    }

    /**
     * Sets a boolean environment variable value in the project's .env file.
     *
     * If the environment variable is already set to a boolean-esque value, its counterpart will be used.
     * For example, if `true` is passed and the current value is `no`, the variable will be set to `yes`.
     *
     * @param string $name The environment variable name
     * @param bool $value The environment variable value
     * @throws Exception if the .env file doesn't exist
     * @since 3.7.24
     */
    public function setBooleanDotEnvVar(string $name, bool $value): void
    {
        $value = match (strtolower((string)App::env($name))) {
            'yes', 'no' => $value ? 'yes' : 'no',
            'on', 'off' => $value ? 'on' : 'off',
            '1', '0' => $value ? '1' : '0',
            default => $value ? 'true' : 'false',
        };

        $this->setDotEnvVar($name, $value);
    }
}
