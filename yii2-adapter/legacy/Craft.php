<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */
use craft\behaviors\CustomFieldBehavior;
use craft\helpers\App;
use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Typecast;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use yii\base\ExitException;
use yii\base\InvalidConfigException;
use yii\helpers\VarDumper;
use yii\web\Request;

use function GuzzleHttp\default_user_agent;

/**
 * Craft is helper class serving common Craft and Yii framework functionality.
 * It encapsulates [[Yii]] and ultimately [[yii\BaseYii]], which provides the actual implementation.
 *
 * @mixin CraftTrait
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.0.0
 */
class Craft extends Yii
{
    /** @deprecated in 5.0.0. [[\craft\enums\Edition::Solo]] should be used instead. */
    public const Solo = 0;

    /** @deprecated in 5.0.0. [[\craft\enums\Edition::Pro]] should be used instead. */
    public const Pro = 2;

    /**
     * @var array The default cookie configuration.
     */
    private static array $_baseCookieConfig;

    /**
     * {@inheritdoc}
     *
     * @deprecated 6.0.0 use {@see Aliases::get()} instead.
     */
    public static function getAlias($alias, $throwException = true)
    {
        // @app/icons/file.svg => @appicons/file.svg
        if (preg_match('/^@app\/icons\/([\w\-]+\.svg)$/', $alias, $match)) {
            $alias = "@appicons/$match[1]";
        }

        return Aliases::get($alias, $throwException);
    }

    /**
     * @deprecated 6.0.0. use {@see \CraftCms\Cms\t()} instead.
     */
    public static function t($category, $message, $params = [], $language = null): string
    {
        return I18N::translate($message, $params, $category, $language);
    }

    /**
     * {@inheritdoc}
     *
     * @template T
     *
     * @param  class-string<T>|array{class:class-string<T>}|array{__class:class-string<T>}|callable():T  $type
     * @return T
     */
    public static function createObject($type, array $params = [])
    {
        if (is_array($type) && isset($type['__class']) && isset($type['class'])) {
            throw new InvalidConfigException('`__class` and `class` cannot both be specified.');
        }

        return parent::createObject($type, $params);
    }

    /**
     * Checks if a string references an environment variable (`$VARIABLE_NAME`)
     * and/or an alias (`@aliasName`), and returns the referenced value.
     *
     * If the string references an environment variable with a value of `true`
     * or `false`, a boolean value will be returned.
     *
     * ---
     *
     * ```php
     * $value1 = Craft::parseEnv('$SMTP_PASSWORD');
     * $value2 = Craft::parseEnv('@webroot');
     * ```
     *
     * @return string|null|false The parsed value, or the original value if it didn’t
     *                           reference an environment variable or alias.
     *
     * @since 3.1.0
     * @deprecated in 3.7.29. [[\CraftCms\Cms\Support\Env::parse()]] should be used instead.
     */
    public static function parseEnv(?string $str = null): string|null|false
    {
        return Env::parse($str);
    }

    /**
     * Checks if a string references an environment variable (`$VARIABLE_NAME`) and returns the referenced
     * boolean value, or `null` if a boolean value can’t be determined.
     *
     * ---
     *
     * ```php
     * $status = Craft::parseBooleanEnv('$SYSTEM_STATUS') ?? false;
     * ```
     *
     * @since 3.7.22
     * @deprecated in 3.7.29. [[\CraftCms\Cms\Support\Env::parseBoolean()]] should be used instead.
     */
    public static function parseBooleanEnv(mixed $value): ?bool
    {
        return Env::parseBoolean($value);
    }

    /**
     * @param $object
     * @param $properties
     *
     * @return object
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Typecast::configure()} instead.
     */
    public static function configure($object, $properties)
    {
        return Typecast::configure($object, $properties);
    }

    /**
     * Displays a variable.
     *
     * @param  mixed  $var  The variable to be dumped.
     * @param  int  $depth  The maximum depth that the dumper should go into the variable.
     * @param  bool  $highlight  Whether the result should be syntax-highlighted.
     * @param  bool  $return  Whether the dump result should be returned instead of output.
     * @return string|null The output, if `$return` is true
     */
    public static function dump(mixed $var, int $depth = 20, bool $highlight = true, bool $return = false): ?string
    {
        if (!$highlight) {
            if ($return) {
                ob_start();
            }
            VarDumper::dump($var, $depth);
            echo "\n";

            return $return ? ob_get_clean() : null;
        }

        $data = (new VarCloner())->cloneVar($var)->withMaxDepth($depth);

        return Craft::$app->getDumper()->dump($data, $return ? true : null);
    }

    /**
     * Displays a variable and ends the request. (“Dump and die”)
     *
     * @param  mixed  $var  The variable to be dumped.
     * @param  int  $depth  The maximum depth that the dumper should go into the variable.
     * @param  bool  $highlight  Whether the result should be syntax-highlighted.
     *
     * @throws ExitException if the application is in testing mode
     */
    public static function dd(mixed $var, int $depth = 20, bool $highlight = true): void
    {
        // Turn off output buffering and discard OB contents
        while (ob_get_length() !== false) {
            // If ob_start() didn't have the PHP_OUTPUT_HANDLER_CLEANABLE flag, ob_get_clean() will cause a PHP notice
            // and return false.
            if (@ob_get_clean() === false) {
                break;
            }
        }

        static::dump($var, $depth, $highlight);
        exit();
    }

    /**
     * Generates and returns a cookie config.
     *
     * @param  array  $config  Any config options that should be included in the config.
     * @param  Request|null  $request  The request object
     * @return array The cookie config array.
     */
    public static function cookieConfig(array $config = [], ?Request $request = null): array
    {
        if (!isset(self::$_baseCookieConfig)) {
            $generalConfig = Cms::config();

            if ($generalConfig->useSecureCookies === 'auto') {
                $request ??= static::$app->getRequest();

                if (!app()->runningInConsole()) {
                    $generalConfig->useSecureCookies = $request->getIsSecureConnection();
                }
            }

            self::$_baseCookieConfig = [
                'domain' => config('session.domain'),
                'secure' => $generalConfig->useSecureCookies,
                'httpOnly' => true,
                'sameSite' => $generalConfig->sameSiteCookieValue,
            ];
        }

        return array_merge(self::$_baseCookieConfig, $config);
    }

    /**
     * Populates CustomFieldBehavior with field handles.
     *
     * Called during application boot after the class is autoloaded by Composer.
     */
    public static function populateCustomFieldBehavior(): void
    {
        if (!isset(static::$app) || !Cms::isInstalled()) {
            return;
        }

        $fieldsService = app(Fields::class);
        CustomFieldBehavior::$fieldHandles = $fieldsService->allFieldHandles();
        CustomFieldBehavior::$generatedFieldHandles = $fieldsService->allGeneratedFieldHandles();
    }

    /**
     * Creates a Guzzle client configured with the given array merged with any default values in config/guzzle.php.
     *
     * @param  array  $config  Guzzle client config settings
     *
     * @deprecated 6.0.0 use {@see Http::create()} instead.
     */
    public static function createGuzzleClient(array $config = []): Client
    {
        // Set the Craft header by default.
        $defaultConfig = [
            'headers' => [
                'User-Agent' => 'Craft/' . static::$app->getVersion() . ' ' . default_user_agent(),
            ],
        ];

        // Grab the config from config/guzzle.php that is used on every Guzzle request.
        $guzzleConfig = config('craft.guzzle', []);
        $generalConfig = Cms::config();

        // Merge everything together
        $guzzleConfig = Arr::merge($defaultConfig, $guzzleConfig, $config);

        if ($generalConfig->httpProxy) {
            $guzzleConfig['proxy'] = $generalConfig->httpProxy;
        }

        return new Client($guzzleConfig);
    }
}
