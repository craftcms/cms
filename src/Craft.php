<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

use craft\behaviors\ContentBehavior;
use craft\behaviors\ContentTrait;
use craft\behaviors\ElementQueryBehavior;
use craft\behaviors\ElementQueryTrait;
use craft\db\Query;
use craft\helpers\FileHelper;
use GuzzleHttp\Client;
use yii\base\ExitException;
use yii\helpers\VarDumper;
use yii\web\Request;

/**
 * Craft is helper class serving common Craft and Yii framework functionality.
 *
 * It encapsulates [[Yii]] and ultimately [[yii\BaseYii]], which provides the actual implementation.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Craft extends Yii
{
    // Constants
    // =========================================================================

    // Edition constants
    const Personal = 0;
    const Client = 1;
    const Pro = 2;

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

    // Public Methods
    // =========================================================================

    /**
     * Displays a variable.
     *
     * @param mixed $var       The variable to be dumped.
     * @param int   $depth     The maximum depth that the dumper should go into the variable. Defaults to 10.
     * @param bool  $highlight Whether the result should be syntax-highlighted. Defaults to true.
     *
     * @return void
     */
    public static function dump($var, int $depth = 10, bool $highlight = true)
    {
        VarDumper::dump($var, $depth, $highlight);
    }

    /**
     * Displays a variable and ends the request. (“Dump and die”)
     *
     * @param mixed $var       The variable to be dumped.
     * @param int   $depth     The maximum depth that the dumper should go into the variable. Defaults to 10.
     * @param bool  $highlight Whether the result should be syntax-highlighted. Defaults to true.
     *
     * @return void
     * @throws ExitException if the application is in testing mode
     */
    public static function dd($var, int $depth = 10, bool $highlight = true)
    {
        VarDumper::dump($var, $depth, $highlight);
        static::$app->end();
    }

    /**
     * Generates and returns a cookie config.
     *
     * @param array        $config  Any config options that should be included in the config.
     * @param Request|null $request The request object
     *
     * @return array The cookie config array.
     */
    public static function cookieConfig(array $config = [], Request $request = null): array
    {
        if (self::$_baseCookieConfig === null) {
            $generalConfig = static::$app->getConfig()->getGeneral();

            $defaultCookieDomain = $generalConfig->defaultCookieDomain;
            $useSecureCookies = $generalConfig->useSecureCookies;

            if ($useSecureCookies === 'auto') {
                if ($request === null) {
                    $request = static::$app->getRequest();
                }

                $useSecureCookies = $request->getIsSecureConnection();
            }

            self::$_baseCookieConfig = [
                'domain' => $defaultCookieDomain,
                'secure' => $useSecureCookies,
                'httpOnly' => true
            ];
        }

        return array_merge(self::$_baseCookieConfig, $config);
    }

    /**
     * Class autoloader.
     *
     * @param string $className
     *
     * @return void
     */
    public static function autoload($className)
    {
        if (
            $className === ContentBehavior::class ||
            $className === ContentTrait::class ||
            $className === ElementQueryBehavior::class ||
            $className === ElementQueryTrait::class
        ) {
            $storedFieldVersion = static::$app->getInfo()->fieldVersion;
            $compiledClassesPath = static::$app->getPath()->getRuntimePath().DIRECTORY_SEPARATOR.'compiled_classes';

            $contentBehaviorFile = $compiledClassesPath.DIRECTORY_SEPARATOR.'ContentBehavior.php';
            $contentTraitFile = $compiledClassesPath.DIRECTORY_SEPARATOR.'ContentTrait.php';
            $elementQueryBehaviorFile = $compiledClassesPath.DIRECTORY_SEPARATOR.'ElementQueryBehavior.php';
            $elementQueryTraitFile = $compiledClassesPath.DIRECTORY_SEPARATOR.'ElementQueryTrait.php';

            $isContentBehaviorFileValid = self::_isFieldAttributesFileValid($contentBehaviorFile, $storedFieldVersion);
            $isContentTraitFileValid = self::_isFieldAttributesFileValid($contentTraitFile, $storedFieldVersion);
            $isElementQueryBehaviorFileValid = self::_isFieldAttributesFileValid($elementQueryBehaviorFile, $storedFieldVersion);
            $isElementQueryTraitFileValid = self::_isFieldAttributesFileValid($elementQueryTraitFile, $storedFieldVersion);

            if (
                $isContentBehaviorFileValid &&
                $isContentTraitFileValid &&
                $isElementQueryBehaviorFileValid &&
                $isElementQueryTraitFileValid
            ) {
                return;
            }

            $properties = [];
            $methods = [];
            $propertyDocs = [];
            $methodDocs = [];

            if (Craft::$app->getIsInstalled()) {
                // Get the field handles
                $fieldHandles = (new Query())
                    ->select(['handle'])
                    ->distinct(true)
                    ->from(['{{%fields}}'])
                    ->column();

                foreach ($fieldHandles as $handle) {
                    $properties[] = <<<EOD
    /**
     * @var mixed Value for field with the handle “{$handle}”.
     */
    public \${$handle};
EOD;

                    $methods[] = <<<EOD
    /**
     * Sets the [[{$handle}]] property.
     * @param mixed \$value The property value
     * @return \\yii\\base\\Component The behavior’s owner component
     */
    public function {$handle}(\$value)
    {
        \$this->{$handle} = \$value;
        return \$this->owner;
    }
EOD;

                    $propertyDocs[] = " * @property mixed \${$handle} Value for the field with the handle “{$handle}”.";
                    $methodDocs[] = " * @method \$this {$handle}(\$value) Sets the [[{$handle}]] property.";
                }
            }

            if (!$isContentBehaviorFileValid) {
                self::_writeFieldAttributesFile(
                    static::$app->getBasePath().DIRECTORY_SEPARATOR.'behaviors'.DIRECTORY_SEPARATOR.'ContentBehavior.php.template',
                    ['{VERSION}', '/* PROPERTIES */'],
                    [$storedFieldVersion, implode("\n\n", $properties)],
                    $contentBehaviorFile
                );
            }

            if (!$isContentTraitFileValid) {
                self::_writeFieldAttributesFile(
                    static::$app->getBasePath().DIRECTORY_SEPARATOR.'behaviors'.DIRECTORY_SEPARATOR.'ContentTrait.php.template',
                    ['{VERSION}', '{PROPERTIES}'],
                    [$storedFieldVersion, implode("\n", $propertyDocs)],
                    $contentTraitFile
                );
            }

            if (!$isElementQueryBehaviorFileValid) {
                self::_writeFieldAttributesFile(
                    static::$app->getBasePath().DIRECTORY_SEPARATOR.'behaviors'.DIRECTORY_SEPARATOR.'ElementQueryBehavior.php.template',
                    ['{VERSION}', '/* METHODS */'],
                    [$storedFieldVersion, implode("\n\n", $methods)],
                    $elementQueryBehaviorFile
                );
            }

            if (!$isElementQueryTraitFileValid) {
                self::_writeFieldAttributesFile(
                    static::$app->getBasePath().DIRECTORY_SEPARATOR.'behaviors'.DIRECTORY_SEPARATOR.'ElementQueryTrait.php.template',
                    ['{VERSION}', '{METHODS}'],
                    [$storedFieldVersion, implode("\n", $methodDocs)],
                    $elementQueryTraitFile
                );
            }
        }
    }

    /**
     * Creates a Guzzle client configured with the given array merged with any default values in config/guzzle.php.
     *
     * @param array $config Guzzle client config settings
     *
     * @return Client
     */
    public static function createGuzzleClient(array $config = []): Client
    {
        // Set the Craft header by default.
        $defaultConfig = [
            'headers' => [
                'User-Agent' => 'Craft/'.Craft::$app->version.' '.\GuzzleHttp\default_user_agent()
            ],
        ];

        // Grab the config from config/guzzle.php that is used on every Guzzle request.
        $guzzleConfig = Craft::$app->getConfig()->getConfigFromFile('guzzle');

        // Merge default into guzzle config.
        $guzzleConfig = array_replace_recursive($guzzleConfig, $defaultConfig);

        // Maybe they want to set some config options specifically for this request.
        $guzzleConfig = array_replace_recursive($guzzleConfig, $config);

        return new Client($guzzleConfig);
    }

    /**
     * Determines if a field attribute file is valid.
     *
     * @param string $path
     * @param string $storedFieldVersion
     *
     * @return bool
     */
    private static function _isFieldAttributesFileValid(string $path, string $storedFieldVersion): bool
    {
        if (file_exists($path)) {
            // Make sure it's up-to-date
            $f = fopen($path, 'rb');
            $line = fgets($f);
            fclose($f);

            if (preg_match('/\/\/ v([a-zA-Z0-9]{12})/', $line, $matches)) {
                if ($matches[1] === $storedFieldVersion) {
                    include $path;

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Writes a field attributes file.
     *
     * @param string   $templatePath
     * @param string[] $search
     * @param string[] $replace
     * @param string   $destinationPath
     */
    private static function _writeFieldAttributesFile(string $templatePath, array $search, array $replace, string $destinationPath)
    {
        $fileContents = file_get_contents($templatePath);
        $fileContents = str_replace($search, $replace, $fileContents);
        FileHelper::writeToFile($destinationPath, $fileContents);

        // Invalidate opcache
        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($destinationPath, true);
        }

        include $destinationPath;
    }
}

spl_autoload_register([Craft::class, 'autoload'], true, true);
