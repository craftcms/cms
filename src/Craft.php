<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

use craft\base\FieldInterface;
use craft\behaviors\ContentBehavior;
use craft\behaviors\ElementQueryBehavior;
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
    // Constants
    // =========================================================================

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

    // Properties
    // =========================================================================

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

    // Public Methods
    // =========================================================================

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
        switch ($className) {
            case ContentBehavior::class:
                self::_autoloadContentBehavior();
                break;
            case ElementQueryBehavior::class:
                self::_autoloadElementQueryBehavior();
                break;
        }
    }

    /**
     * Autoloads (and possibly generates) ContentBehavior.php
     *
     * @param bool $write
     * @param bool $load
     * @return bool Whether the file was written/loaded
     */
    private static function _autoloadContentBehavior(bool $write = true, bool $load = true)
    {
        $storedFieldVersion = static::$app->getInfo()->fieldVersion;
        $compiledClassesPath = static::$app->getPath()->getCompiledClassesPath();
        $filePath = $compiledClassesPath . DIRECTORY_SEPARATOR . 'ContentBehavior.php';

        if (self::_loadFieldAttributesFile($filePath, $storedFieldVersion)) {
            return false;
        }

        $fields = self::_fields();

        if (empty($fields)) {
            self::_generateContentBehavior([], $filePath, $storedFieldVersion, $write, $load);
        } else {
            if ($load) {
                // First generate a basic version without real field value types, and load it into memory
                $fieldHandles = [];
                foreach ($fields as $field) {
                    $fieldHandles[$field['handle']]['mixed'] = true;
                }
                self::_generateContentBehavior($fieldHandles, $filePath, $storedFieldVersion, false, true);
            }

            if ($write) {
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
                        // Add a leading `\` if there is a namespace
                        if (strpos($type, '\\') !== false) {
                            $type = '\\' . $type;
                        }
                        $fieldHandles[$field['handle']][$type] = true;
                    }
                }
                self::_generateContentBehavior($fieldHandles, $filePath, $storedFieldVersion, true, false);
            }
        }

        return true;
    }

    /**
     * @param array $fieldHandles
     * @param string $contentBehaviorFile
     * @param string $storedFieldVersion
     * @param bool $write
     * @param bool $load
     * @throws \yii\base\ErrorException
     */
    private static function _generateContentBehavior(array $fieldHandles, string $contentBehaviorFile, string $storedFieldVersion, bool $write, bool $load)
    {
        $handles = [];
        $properties = [];

        foreach ($fieldHandles as $handle => $types) {
            $handles[] = <<<EOD
        '{$handle}' => true,
EOD;

            if ($load) {
                $properties[] = <<<EOD
    public \${$handle};
EOD;
            } else {
                $phpDocTypes = implode('|', array_keys($types));
                $properties[] = <<<EOD
    /**
     * @var {$phpDocTypes} Value for field with the handle “{$handle}”.
     */
    public \${$handle};
EOD;
            }
        }

        self::_generateBehavior(
            static::$app->getBasePath() . DIRECTORY_SEPARATOR . 'behaviors' . DIRECTORY_SEPARATOR . 'ContentBehavior.php.template',
            ['{VERSION}', '/* HANDLES */', '/* PROPERTIES */'],
            [$storedFieldVersion, implode("\n", $handles), implode("\n\n", $properties)],
            $contentBehaviorFile,
            $write,
            $load
        );
    }

    /**
     * Autoloads (and possibly generates) ElementQueryBehaviorBehavior.php
     */
    private static function _autoloadElementQueryBehavior()
    {
        // Make sure ContentBehavior is autoloaded first
        $generatedContentBehavior = (
            !class_exists(ContentBehavior::class, false) &&
            self::_autoloadContentBehavior(false, true)
        );

        $storedFieldVersion = static::$app->getInfo()->fieldVersion;
        $compiledClassesPath = static::$app->getPath()->getCompiledClassesPath();
        $filePath = $compiledClassesPath . DIRECTORY_SEPARATOR . 'ElementQueryBehavior.php';

        if (!self::_loadFieldAttributesFile($filePath, $storedFieldVersion)) {
            $methods = [];

            foreach (self::_fields() as $field) {
                $methods[] = <<<EOD
 * @method self {$field['handle']}(mixed \$value) Sets the [[{$field['handle']}]] property
EOD;
            }

            self::_generateBehavior(
                static::$app->getBasePath() . DIRECTORY_SEPARATOR . 'behaviors' . DIRECTORY_SEPARATOR . 'ElementQueryBehavior.php.template',
                ['{VERSION}', '{METHOD_DOCS}'],
                [$storedFieldVersion, implode("\n", $methods)],
                $filePath,
                true,
                true
            );
        }

        if ($generatedContentBehavior) {
            // Now write ContentBehavior.php, now that
            self::_autoloadContentBehavior(true, false);
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

        include $path;
        return true;
    }

    /**
     * Writes a field attributes file.
     *
     * @param string $templatePath
     * @param string[] $search
     * @param string[] $replace
     * @param string $filePath
     * @param bool $write
     * @param bool $load
     * @throws \yii\base\ErrorException
     */
    private static function _generateBehavior(string $templatePath, array $search, array $replace, string $filePath, bool $write, bool $load)
    {
        $fileContents = file_get_contents($templatePath);
        $fileContents = str_replace($search, $replace, $fileContents);

        if ($write) {
            FileHelper::writeToFile($filePath, $fileContents);
            clearstatcache(true, $filePath);
            if ($load) {
                include $filePath;
            }
        } else if ($load) {
            // Just evaluate the code
            eval(preg_replace('/^<\?php\s*/', '', $fileContents));
        }
    }
}

spl_autoload_register([Craft::class, 'autoload'], true, true);
