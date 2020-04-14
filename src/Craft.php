<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

use craft\base\FieldInterface;
use craft\behaviors\CustomFieldBehavior;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Component;
use craft\helpers\FileHelper;
use GuzzleHttp\Client;
use yii\base\ExitException;
use yii\db\Expression;
use yii\helpers\VarDumper;
use yii\web\Request;

/**
 * Craft is helper class serving common Craft and Yii framework functionality.
 * It encapsulates [[Yii]] and ultimately [[yii\BaseYii]], which provides the actual implementation.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Craft extends Yii
{
    // Edition constants
    const Solo = 0;
    const Pro = 1;

    /**
     * @deprecated in 3.0.0. Use [[Solo]] instead.
     */
    const Personal = 0;
    /**
     * @deprecated in 3.0.0. Use [[Pro]] instead.
     */
    const Client = 1;

    /**
     * @var \craft\web\Application|\craft\console\Application The application instance.
     */
    public static $app;

    /**
     * @var array The default cookie configuration.
     */
    private static $_baseCookieConfig;

    /**
     * @var array Field info for autoload()
     */
    private static $_fields;

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
     * @return string|bool|null The parsed value, or the original value if it didn’t
     * reference an environment variable and/or alias.
     * @since 3.1.0
     */
    public static function parseEnv(string $str = null)
    {
        if ($str === null) {
            return null;
        }

        if (preg_match('/^\$(\w+)$/', $str, $matches)) {
            $value = getenv($matches[1]);
            if ($value !== false) {
                switch (strtolower($value)) {
                    case 'true':
                        return true;
                    case 'false':
                        return false;
                }
                $str = $value;
            }
        }

        return static::getAlias($str, false) ?: $str;
    }

    /**
     * Displays a variable.
     *
     * @param mixed $var The variable to be dumped.
     * @param int $depth The maximum depth that the dumper should go into the variable. Defaults to 10.
     * @param bool $highlight Whether the result should be syntax-highlighted. Defaults to true.
     */
    public static function dump($var, int $depth = 10, bool $highlight = true)
    {
        VarDumper::dump($var, $depth, $highlight);
    }

    /**
     * Displays a variable and ends the request. (“Dump and die”)
     *
     * @param mixed $var The variable to be dumped.
     * @param int $depth The maximum depth that the dumper should go into the variable. Defaults to 10.
     * @param bool $highlight Whether the result should be syntax-highlighted. Defaults to true.
     * @throws ExitException if the application is in testing mode
     */
    public static function dd($var, int $depth = 10, bool $highlight = true)
    {
        // Turn off output buffering and discard OB contents
        while (ob_get_length() !== false) {
            // If ob_start() didn't have the PHP_OUTPUT_HANDLER_CLEANABLE flag, ob_get_clean() will cause a PHP notice
            // and return false.
            if (@ob_get_clean() === false) {
                break;
            }
        }

        VarDumper::dump($var, $depth, $highlight);
        exit();
    }

    /**
     * Generates and returns a cookie config.
     *
     * @param array $config Any config options that should be included in the config.
     * @param Request|null $request The request object
     * @return array The cookie config array.
     */
    public static function cookieConfig(array $config = [], Request $request = null): array
    {
        if (self::$_baseCookieConfig === null) {
            $generalConfig = static::$app->getConfig()->getGeneral();

            if ($generalConfig->useSecureCookies === 'auto') {
                if ($request === null) {
                    $request = static::$app->getRequest();
                }

                $generalConfig->useSecureCookies = $request->getIsSecureConnection();
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
     * @param string $className
     */
    public static function autoload($className)
    {
        if ($className === CustomFieldBehavior::class) {
            self::_autoloadCustomFieldBehavior();
        }
    }

    /**
     * Autoloads (and possibly generates) `CustomFieldBehavior.php`
     */
    private static function _autoloadCustomFieldBehavior()
    {
        $storedFieldVersion = static::$app->getInfo()->fieldVersion;
        $compiledClassesPath = static::$app->getPath()->getCompiledClassesPath();
        $filePath = $compiledClassesPath . DIRECTORY_SEPARATOR . 'CustomFieldBehavior.php';

        if (self::_loadFieldAttributesFile($filePath, $storedFieldVersion)) {
            return;
        }

        $fields = self::_fields();

        if (empty($fields)) {
            // Write and load it simultaneously since there are no custom fields to worry about
            self::_generateCustomFieldBehavior([], $filePath, $storedFieldVersion, true, true);
            return;
        }

        // First generate a basic version without real field value types, and load it into memory
        $fieldHandles = [];
        foreach ($fields as $field) {
            $fieldHandles[$field['handle']]['mixed'] = true;
        }
        self::_generateCustomFieldBehavior($fieldHandles, $filePath, $storedFieldVersion, false, true);

        // Now generate it again, this time with the correct field value types
        $fieldHandles = [];
        foreach ($fields as $field) {
            /** @var FieldInterface|string $fieldClass */
            $fieldClass = $field['type'];
            if (Component::validateComponentClass($fieldClass, FieldInterface::class)) {
                $types = explode('|', $fieldClass::valueType());
            } else {
                $types = ['mixed'];
            }
            foreach ($types as $type) {
                $type = trim($type, ' \\');
                // Add a leading `\` if it's not a variable, self-reference, or primitive type
                if (!preg_match('/^(\$.*|(self|static|bool|boolean|int|integer|float|double|string|array|object|callable|callback|iterable|resource|null|mixed|number|void)(\[\])?)$/i', $type)) {
                    $type = '\\' . $type;
                }
                $fieldHandles[$field['handle']][$type] = true;
            }
        }
        self::_generateCustomFieldBehavior($fieldHandles, $filePath, $storedFieldVersion, true, false);
    }

    /**
     * @param array $fieldHandles
     * @param string $filePath
     * @param string $storedFieldVersion
     * @param bool $write
     * @param bool $load
     * @throws \yii\base\ErrorException
     */
    private static function _generateCustomFieldBehavior(array $fieldHandles, string $filePath, string $storedFieldVersion, bool $write, bool $load)
    {
        $methods = [];
        $handles = [];
        $properties = [];

        foreach ($fieldHandles as $handle => $types) {
            $methods[] = <<<EOD
 * @method self {$handle}(mixed \$value) Sets the [[{$handle}]] property
EOD;

            $handles[] = <<<EOD
        '{$handle}' => true,
EOD;

            $phpDocTypes = implode('|', array_keys($types));
            $properties[] = <<<EOD
    /**
     * @var {$phpDocTypes} Value for field with the handle “{$handle}”.
     */
    public \${$handle};
EOD;
        }

        // Load the template
        $fileContents = file_get_contents(static::$app->getBasePath() . DIRECTORY_SEPARATOR . 'behaviors' .
            DIRECTORY_SEPARATOR . 'CustomFieldBehavior.php.template');

        // Replace placeholders with generated code
        $fileContents = str_replace(
            [
                '{VERSION}',
                '{METHOD_DOCS}',
                '/* HANDLES */',
                '/* PROPERTIES */',
            ],
            [
                $storedFieldVersion,
                implode("\n", $methods),
                implode("\n", $handles),
                implode("\n\n", $properties),
            ],
            $fileContents);

        if ($write) {
            $tmpFile = dirname($filePath) . DIRECTORY_SEPARATOR . uniqid(pathinfo($filePath, PATHINFO_FILENAME), true) . '.php';
            FileHelper::writeToFile($tmpFile, $fileContents);
            rename($tmpFile, $filePath);
            FileHelper::invalidate($filePath);
            if ($load) {
                include $filePath;
            }
        } else if ($load) {
            // Just evaluate the code
            eval(preg_replace('/^<\?php\s*/', '', $fileContents));
        }
    }

    /**
     * @return array
     */
    private static function _fields(): array
    {
        if (self::$_fields !== null) {
            return self::$_fields;
        }

        if (!static::$app->getIsInstalled()) {
            return [];
        }

        // Properties are case-sensitive, so get all the binary-unique field handles
        if (static::$app->getDb()->getIsMysql()) {
            $handleColumn = new Expression('binary [[handle]] as [[handle]]');
        } else {
            $handleColumn = 'handle';
        }

        // Create an array of field handles and their types
        return self::$_fields = (new Query())
            ->from([Table::FIELDS])
            ->select([$handleColumn, 'type'])
            ->all();
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
                'User-Agent' => 'Craft/' . static::$app->getVersion() . ' ' . \GuzzleHttp\default_user_agent()
            ],
        ];

        // Grab the config from config/guzzle.php that is used on every Guzzle request.
        $guzzleConfig = static::$app->getConfig()->getConfigFromFile('guzzle');

        // Merge default into guzzle config.
        $guzzleConfig = array_replace_recursive($guzzleConfig, $defaultConfig);

        // Maybe they want to set some config options specifically for this request.
        $guzzleConfig = array_replace_recursive($guzzleConfig, $config);

        return new Client($guzzleConfig);
    }

    /**
     * Loads a field attribute file, if it’s valid.
     *
     * @param string $path
     * @param string $storedFieldVersion
     * @return bool
     */
    private static function _loadFieldAttributesFile(string $path, string $storedFieldVersion): bool
    {
        if (!file_exists($path)) {
            return false;
        }

        // Make sure it's up-to-date
        $f = fopen($path, 'rb');
        $line = fgets($f);
        fclose($f);

        if (strpos($line, "// v{$storedFieldVersion}") === false) {
            return false;
        }

        try {
            include $path;
        } catch (\Throwable $e) {
            return false;
        }

        return true;
    }
}

spl_autoload_register([Craft::class, 'autoload'], true, true);
