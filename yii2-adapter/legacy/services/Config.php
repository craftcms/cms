<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\config\DbConfig;
use craft\config\GeneralConfig as LegacyGeneralConfig;
use CraftCms\Cms\Config\BaseConfig;
use CraftCms\Cms\Config\GeneralConfig as NewGeneralConfig;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\Typecast;
use Illuminate\Support\Facades\Config as ConfigFacade;
use InvalidArgumentException;
use RuntimeException;
use Throwable;
use yii\base\Component;

/**
 * The Config service provides APIs for retrieving the values of Craft’s [config settings](http://craftcms.com/docs/config-settings),
 * as well as the values of any plugins’ config settings.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getConfig()|`Craft::$app->getConfig()`]].
 *
 * @property-read DbConfig $db the DB config settings
 * @property-read LegacyGeneralConfig $general the general config settings
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
     * @var string The application type (`web` or `console`).
     */
    public string $appType;

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
     * @var string|null
     * @see getConfigFromFile()
     * @see getLoadingConfigFile()
     */
    private ?string $_loadingConfigFile = null;

    /**
     * Returns all of the config settings for a given category.
     *
     * @param string $category The config category
     * @return object The config settings
     * @throws InvalidArgumentException if $category is invalid
     */
    public function getConfigSettings(string $category): object
    {
        if (!isset($this->_configSettings[$category])) {
            $config = $this->_createConfigObj($category, $category, null);

            if ($category !== self::CATEGORY_CUSTOM && isset($this->appType)) {
                // See if an application type-specific config exists (general.web.php / general.console.php)
                /** @var LegacyGeneralConfig|DbConfig $config */
                $config = $this->_createConfigObj($category, "$category.$this->appType", $config);
            }

            $this->_configSettings[$category] = $config;
        }

        return $this->_configSettings[$category];
    }

    private function _createConfigObj(string $category, string $filename, BaseConfig|\craft\config\BaseConfig|null $existingConfig): object
    {
        $config = ConfigFacade::get("craft.$filename", []);

        if ($existingConfig && empty($config)) {
            return $existingConfig;
        }

        if ($category === self::CATEGORY_GENERAL) {
            $configClass = LegacyGeneralConfig::class;
            $envPrefix = 'CRAFT_';

            if ($config instanceof NewGeneralConfig) {
                $config = LegacyGeneralConfig::__set_state($config->toArray());
            }
        } else {
            switch ($category) {
                case self::CATEGORY_CUSTOM:
                    return (object)$config;
                case self::CATEGORY_DB:
                    $configClass = DbConfig::class;
                    $envPrefix = 'CRAFT_DB_';
                    break;
                default:
                    throw new InvalidArgumentException("Invalid config category: $category");
            }
        }

        if (is_callable($config)) {
            $config = $config($existingConfig ?? $configClass::create());
        }

        if ($config instanceof NewGeneralConfig) {
            $config = LegacyGeneralConfig::__set_state($config->toArray());
        }

        // Get any environment value overrides
        $envConfig = Env::config($configClass, $envPrefix);

        // If $config is already a BaseConfig object, assign the env overrides to it and return
        if ($config instanceof BaseConfig) {
            Typecast::properties($configClass, $envConfig);

            foreach ($envConfig as $name => $value) {
                // Use the fluent methods when possible, in case it has any value normalization logic
                if (method_exists($config, $name)) {
                    try {
                        $config->$name($value);
                        continue;
                    } catch (Throwable) {
                    }
                    $config->$name = $value;
                }
            }

            return $config;
        }

        $loadingConfig = $this->_loadingConfigFile;
        $this->_loadingConfigFile = $filename;

        $config = array_merge($config, $envConfig);
        Typecast::properties($configClass, $config);

        if ($existingConfig !== null) {
            Typecast::configure($existingConfig, $config);
            $config = $existingConfig;
        } else {
            if ($category === self::CATEGORY_GENERAL) {
                $config = $configClass::__set_state($config);
            } else {
                /** @var class-string<DbConfig> $configClass */
                $config = new $configClass($config);
            }
        }

        $this->_loadingConfigFile = $loadingConfig;
        return $config;
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
     * @return LegacyGeneralConfig
     * @deprecated in 6.0.0. Use `app(\CraftCms\Cms\Config\GeneralConfig::class)` (PHP) or `app.config.craft.general` (Twig) instead.
     */
    public function getGeneral(): LegacyGeneralConfig
    {
        Deprecator::log('Craft::$app->config->general', 'Craft::$app->config->general is deprecated. Use `CraftCms\Cms\Cms::config()` (PHP) or `app.config.craft.general` (Twig) instead.');

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
     *
     * @return array|callable|BaseConfig
     * @deprecated in 6.0.0. Use `\Illuminate\Support\Facades\Config::get("craft.$filename")` instead.
     */
    public function getConfigFromFile(string $filename): array|callable|BaseConfig
    {
        return ConfigFacade::get("craft.$filename", []);
    }

    /**
     * Returns the config filename currently being loaded.
     *
     * @return string|null
     * @since 4.2.0
     */
    public function getLoadingConfigFile(): ?string
    {
        return $this->_loadingConfigFile;
    }

    /**
     * Returns the path to the .env file (regardless of whether it exists).
     *
     * @return string
     * @deprecated in 6.0.0. Use `app()->environmentFilePath()` instead.
     */
    public function getDotEnvPath(): string
    {
        return $this->_dotEnvPath ?? ($this->_dotEnvPath = app()->environmentFilePath());
    }

    /**
     * Sets an environment variable value in the project's `.env` file.
     *
     * @param string $name The environment variable name
     * @param string|false $value The environment variable value, or `false` if it should be removed.
     * @throws RuntimeException if the .env file doesn't exist
     * @deprecated in 6.0.0. Use `\CraftCms\Cms\Support\Env::writeVariable()` or `\CraftCms\Cms\Support\Env::removeVariable()` instead.
     */
    public function setDotEnvVar(string $name, string|false $value): void
    {
        $path = app()->environmentFilePath();

        if ($value === false) {
            Env::removeVariable($name, $path);
        } else {
            Env::writeVariable($name, $value, $path, overwrite: true);
        }

        // Now actually set the environment variable
        if ($value === false) {
            unset($_SERVER[$name]);
        } else {
            $_SERVER[$name] = $value;
        }
    }

    /**
     * Sets a boolean environment variable value in the project's .env file.
     *
     * If the environment variable is already set to a boolean-esque value, its counterpart will be used.
     * For example, if `true` is passed and the current value is `no`, the variable will be set to `yes`.
     *
     * @param string $name The environment variable name
     * @param bool $value The environment variable value
     *
     * @throws RuntimeException if the .env file doesn't exist
     * @since 3.7.24
     */
    public function setBooleanDotEnvVar(string $name, bool $value): void
    {
        $value = match (strtolower((string)Env::get($name))) {
            'yes', 'no' => $value ? 'yes' : 'no',
            'on', 'off' => $value ? 'on' : 'off',
            '1', '0' => $value ? '1' : '0',
            default => $value ? 'true' : 'false',
        };

        Env::writeVariable($name, $value, app()->environmentFilePath(), true);
    }
}
