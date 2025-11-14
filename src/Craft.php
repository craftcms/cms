<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

use craft\base\FieldInterface;
use craft\behaviors\CustomFieldBehavior;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use GuzzleHttp\Client;
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
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Craft extends Yii
{
    /** @deprecated in 5.0.0. [[\craft\enums\CmsEdition::Solo]] should be used instead. */
    public const Solo = 0;
    /** @deprecated in 5.0.0. [[\craft\enums\CmsEdition::Pro]] should be used instead. */
    public const Pro = 2;

    /**
     * @var array The default cookie configuration.
     */
    private static array $_baseCookieConfig;

    /**
     * @inheritdoc
     */
    public static function getAlias($alias, $throwException = true)
    {
        // @app/icons/file.svg => @appicons/file.svg
        if (preg_match('/^@app\/icons\/([\w\-]+\.svg)$/', $alias, $match)) {
            $alias = "@appicons/$match[1]";
        }

        return parent::getAlias($alias, $throwException);
    }

    /**
     * @inheritdoc
     * @template T
     * @param class-string<T>|array|callable $type
     * @phpstan-param class-string<T>|array{class:class-string<T>}|callable():T $type
     * @param array $params
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
     * @param string|null $str
     * @return string|null|false The parsed value, or the original value if it didn’t
     * reference an environment variable or alias.
     * @since 3.1.0
     * @deprecated in 3.7.29. [[App::parseEnv()]] should be used instead.
     */
    public static function parseEnv(?string $str = null): string|null|false
    {
        return App::parseEnv($str);
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
     * @param mixed $value
     * @return bool|null
     * @since 3.7.22
     * @deprecated in 3.7.29. [[App::parseBooleanEnv()]] should be used instead.
     */
    public static function parseBooleanEnv(mixed $value): ?bool
    {
        return App::parseBooleanEnv($value);
    }

    /**
     * Displays a variable.
     *
     * @param mixed $var The variable to be dumped.
     * @param int $depth The maximum depth that the dumper should go into the variable.
     * @param bool $highlight Whether the result should be syntax-highlighted.
     * @param bool $return Whether the dump result should be returned instead of output.
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
     * @param mixed $var The variable to be dumped.
     * @param int $depth The maximum depth that the dumper should go into the variable.
     * @param bool $highlight Whether the result should be syntax-highlighted.
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
     * @param array $config Any config options that should be included in the config.
     * @param Request|null $request The request object
     * @return array The cookie config array.
     */
    public static function cookieConfig(array $config = [], ?Request $request = null): array
    {
        if (!isset(self::$_baseCookieConfig)) {
            $generalConfig = static::$app->getConfig()->getGeneral();

            if ($generalConfig->useSecureCookies === 'auto') {
                $request ??= static::$app->getRequest();

                if (!$request->getIsConsoleRequest()) {
                    $generalConfig->useSecureCookies = $request->getIsSecureConnection();
                }
            }

            self::$_baseCookieConfig = [
                'domain' => $generalConfig->defaultCookieDomain,
                'secure' => $generalConfig->useSecureCookies,
                'httpOnly' => true,
                'sameSite' => $generalConfig->sameSiteCookieValue,
            ];
        }

        return array_merge(self::$_baseCookieConfig, $config);
    }

    /**
     * Class autoloader.
     *
     * @param class-string $className
     */
    public static function autoload($className): void
    {
        if ($className === CustomFieldBehavior::class) {
            self::_autoloadCustomFieldBehavior();
        }
    }

    /**
     * Autoloads (and possibly generates) `CustomFieldBehavior.php`
     */
    private static function _autoloadCustomFieldBehavior(): void
    {
        if (!isset(static::$app)) {
            // Nothing we can do about it yet
            return;
        }

        if (!static::$app->getIsInstalled()) {
            // Just load an empty CustomFieldBehavior into memory
            self::_generateCustomFieldBehavior([], [], [], null, false, true);
            return;
        }

        $fieldsService = Craft::$app->getFields();
        $storedFieldVersion = $fieldsService->getFieldVersion();
        $compiledClassesPath = static::$app->getPath()->getCompiledClassesPath();
        $fieldVersionExists = $storedFieldVersion !== null;

        if (!$fieldVersionExists) {
            // Just make up a temporary one
            $storedFieldVersion = StringHelper::randomString(12);
        }

        $filePath = $compiledClassesPath . DIRECTORY_SEPARATOR . "CustomFieldBehavior_$storedFieldVersion.php";

        if ($fieldVersionExists && file_exists($filePath)) {
            include $filePath;
            return;
        }

        [$fields, $generatedFieldHandles] = self::_fields();

        // First generate a basic version without real field value types, and load it into memory
        $fieldHandles = [];
        $fieldTypes = [];
        foreach ($fields as $field) {
            $fieldHandles[] = $field->handle;
            $fieldTypes[$field->handle]['mixed'] = true;
        }
        foreach ($generatedFieldHandles as $handle) {
            $fieldTypes[$handle]['mixed'] = true;
        }
        self::_generateCustomFieldBehavior($fieldHandles, $generatedFieldHandles, $fieldTypes, $filePath, false, true);

        // Now generate it again, this time with the correct field value types
        $fieldTypes = [];
        foreach ($fields as $field) {
            $types = explode('|', $field::phpType());
            foreach ($types as $type) {
                $type = trim($type, ' \\');
                // Add a leading `\` if it’s not a variable, self-reference, or primitive type
                if (!preg_match('/^(\$.*|(self|static|bool|boolean|int|integer|float|double|string|array|object|callable|callback|iterable|resource|null|mixed|number|void)(\[\])?)$/i', $type)) {
                    $type = '\\' . $type;
                }
                $fieldTypes[$field->handle][$type] = true;
            }
        }
        foreach ($generatedFieldHandles as $handle) {
            $fieldTypes[$handle]['string'] = true;
            $fieldTypes[$handle]['null'] = true;
        }
        self::_generateCustomFieldBehavior($fieldHandles, $generatedFieldHandles, $fieldTypes, $filePath, true, false);

        // Generate a new field version if we need one
        if (!$fieldVersionExists) {
            try {
                $fieldsService->updateFieldVersion();
            } catch (Throwable) {
                // Craft probably isn't installed yet.
            }
        }
    }

    /**
     * @param array $fieldHandles
     * @param array $generatedFieldHandles
     * @param array $fieldTypes
     * @param string|null $filePath
     * @param bool $write
     * @param bool $load
     * @throws \yii\base\ErrorException
     */
    private static function _generateCustomFieldBehavior(
        array $fieldHandles,
        array $generatedFieldHandles,
        array $fieldTypes,
        ?string $filePath,
        bool $write,
        bool $load,
    ): void {
        $methods = [];
        $fieldHandlesPhp = [];
        $generatedFieldHandlesPhp = [];
        $properties = [];

        foreach ($fieldHandles as $handle) {
            $fieldHandlesPhp[] = <<<EOD
        '$handle' => true,
EOD;
        }

        foreach ($generatedFieldHandles as $handle) {
            $generatedFieldHandlesPhp[] = <<<EOD
        '$handle' => true,
EOD;
        }

        foreach ($fieldTypes as $handle => $types) {
            $methods[] = <<<EOD
 * @method \$this $handle(mixed \$value) Sets the [[$handle]] property
EOD;

            $phpDocTypes = implode('|', array_keys($types));
            $properties[] = <<<EOD
    /**
     * @var $phpDocTypes Value for field with the handle “{$handle}”.
     */
    public \$$handle;
EOD;
        }

        // Load the template
        $templatePath = static::$app->getBasePath() . DIRECTORY_SEPARATOR . 'behaviors' . DIRECTORY_SEPARATOR . 'CustomFieldBehavior.php.template';
        FileHelper::invalidate($templatePath);
        $fileContents = file_get_contents($templatePath);

        // Replace placeholders with generated code
        $fileContents = str_replace(
            [
                '{METHOD_DOCS}',
                '/* HANDLES */',
                '/* GENERATED HANDLES */',
                '/* PROPERTIES */',
            ],
            [
                implode("\n", $methods),
                implode("\n", $fieldHandlesPhp),
                implode("\n", $generatedFieldHandlesPhp),
                implode("\n\n", $properties),
            ],
            $fileContents);

        if ($write) {
            $dir = dirname($filePath);
            $tmpFile = $dir . DIRECTORY_SEPARATOR . uniqid(pathinfo($filePath, PATHINFO_FILENAME), true) . '.php';
            FileHelper::writeToFile($tmpFile, $fileContents);
            rename($tmpFile, $filePath);
            FileHelper::invalidate($filePath);
            if ($load) {
                include $filePath;
            }

            // Delete any CustomFieldBehavior files that are over 10 seconds old
            $basename = basename($filePath);
            $time = DateTimeHelper::currentTimeStamp() - 10;
            FileHelper::clearDirectory($dir, [
                'filter' => function(string $path) use ($basename, $time): bool {
                    $b = basename($path);
                    return (
                        $b !== $basename &&
                        str_starts_with($b, 'CustomFieldBehavior') &&
                        filemtime($path) < $time
                    );
                },
            ]);
        } elseif ($load) {
            // Just evaluate the code
            eval(preg_replace('/^<\?php\s*/', '', $fileContents));
        }
    }

    /**
     * @return array{0:FieldInterface[],1:string[]}
     */
    private static function _fields(): array
    {
        $fieldsService = static::$app->getFields();
        /** @var FieldInterface[] $fields */
        $fields = $fieldsService->getAllFields(false);
        $generatedFieldHandles = [];

        foreach ($fieldsService->getAllLayouts() as $layout) {
            foreach ($layout->getCustomFields() as $field) {
                if ($field->handle !== $field->layoutElement->getOriginalHandle()) {
                    $fields[] = $field;
                }
            }
            foreach ($layout->getGeneratedFields() as $generatedField) {
                $handle = $generatedField['handle'] ?? '';
                if ($handle !== '') {
                    $generatedFieldHandles[$handle] = true;
                }
            }
        }

        // Sort custom fields by handle
        // Note: we can't use array_multisort here! https://github.com/craftcms/cms/issues/17556
        usort($fields, fn(FieldInterface $a, FieldInterface $b) => $a->handle <=> $b->handle);

        return [$fields, array_keys($generatedFieldHandles)];
    }

    /**
     * Creates a Guzzle client configured with the given array merged with any default values in config/guzzle.php.
     *
     * @param array $config Guzzle client config settings
     * @return Client
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
        $configService = static::$app->getConfig();
        $guzzleConfig = $configService->getConfigFromFile('guzzle');
        $generalConfig = $configService->getGeneral();

        // Merge everything together
        $guzzleConfig = ArrayHelper::merge($defaultConfig, $guzzleConfig, $config);

        if ($generalConfig->httpProxy) {
            $guzzleConfig['proxy'] = $generalConfig->httpProxy;
        }

        return Craft::createObject(Client::class, [$guzzleConfig]);
    }
}

spl_autoload_register([Craft::class, 'autoload'], true, true);
