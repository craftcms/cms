<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

use craft\app\db\Query;
use craft\app\helpers\Io;
use yii\helpers\VarDumper;
use yii\web\Request;

/**
 * Craft is helper class serving common Craft and Yii framework functionality.
 *
 * It encapsulates [[Yii]] and ultimately [[YiiBase]], which provides the actual implementation.
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
     * @var \craft\app\web\Application The application instance.
     *
     * This may return a [[\craft\app\console\Application]] instance if this is a console request.
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
     * @param mixed   $var       The variable to be dumped.
     * @param integer $depth     The maximum depth that the dumper should go into the variable. Defaults to 10.
     * @param boolean $highlight Whether the result should be syntax-highlighted. Defaults to true.
     *
     * @return void
     */
    public static function dump($var, $depth = 10, $highlight = true)
    {
        VarDumper::dump($var, $depth, $highlight);
    }

    /**
     * Displays a variable and ends the request. (“Dump and die”)
     *
     * @param mixed   $var       The variable to be dumped.
     * @param integer $depth     The maximum depth that the dumper should go into the variable. Defaults to 10.
     * @param boolean $highlight Whether the result should be syntax-highlighted. Defaults to true.
     *
     * @return void
     */
    public static function dd($var, $depth = 10, $highlight = true)
    {
        VarDumper::dump($var, $depth, $highlight);
        static::$app->end();
    }

    /**
     * Generates and returns a cookie config.
     *
     * @param array|null $config  Any config options that should be included in the config.
     * @param Request    $request The request object
     *
     * @return array The cookie config array.
     */
    public static function getCookieConfig($config = [], $request = null)
    {
        if (!isset(static::$_baseCookieConfig)) {
            $configService = static::$app->getConfig();

            $defaultCookieDomain = $configService->get('defaultCookieDomain');
            $useSecureCookies = $configService->get('useSecureCookies');

            if ($useSecureCookies === 'auto') {
                if ($request === null) {
                    $request = static::$app->getRequest();
                }

                $useSecureCookies = $request->getIsSecureConnection();
            }

            static::$_baseCookieConfig = [
                'domain' => $defaultCookieDomain,
                'secure' => $useSecureCookies,
                'httpOnly' => true
            ];
        }

        return array_merge(static::$_baseCookieConfig, $config);
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
            $className === \craft\app\behaviors\ContentBehavior::class ||
            $className === \craft\app\behaviors\ContentTrait::class ||
            $className === \craft\app\behaviors\ElementQueryBehavior::class ||
            $className === \craft\app\behaviors\ElementQueryTrait::class
        ) {
            $storedFieldVersion = static::$app->getInfo('fieldVersion');
            $compiledClassesPath = static::$app->getPath()->getRuntimePath().'/compiled_classes';

            $contentBehaviorFile = $compiledClassesPath.'/ContentBehavior.php';
            $contentTraitFile = $compiledClassesPath.'/ContentTrait.php';
            $elementQueryBehaviorFile = $compiledClassesPath.'/ElementQueryBehavior.php';
            $elementQueryTraitFile = $compiledClassesPath.'/ElementQueryTrait.php';

            if (
                static::_isFieldAttributesFileValid($contentBehaviorFile, $storedFieldVersion) &&
                static::_isFieldAttributesFileValid($contentTraitFile, $storedFieldVersion) &&
                static::_isFieldAttributesFileValid($elementQueryBehaviorFile, $storedFieldVersion) &&
                static::_isFieldAttributesFileValid($elementQueryTraitFile, $storedFieldVersion)
            ) {
                return;
            }

            // Get the field handles
            $fieldHandles = (new Query())
                ->select('handle')
                ->distinct(true)
                ->from('{{%fields}}')
                ->column();

            $properties = [];
            $methods = [];
            $propertyDocs = [];
            $methodDocs = [];

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

            static::_writeFieldAttributesFile(
                static::$app->getPath()->getAppPath().'/behaviors/ContentBehavior.php.template',
                ['{VERSION}', '/* PROPERTIES */'],
                [$storedFieldVersion, implode("\n\n", $properties)],
                $contentBehaviorFile
            );

            static::_writeFieldAttributesFile(
                static::$app->getPath()->getAppPath().'/behaviors/ContentTrait.php.template',
                ['{VERSION}', '{PROPERTIES}'],
                [$storedFieldVersion, implode("\n", $propertyDocs)],
                $contentTraitFile
            );

            static::_writeFieldAttributesFile(
                static::$app->getPath()->getAppPath().'/behaviors/ElementQueryBehavior.php.template',
                ['{VERSION}', '/* METHODS */'],
                [$storedFieldVersion, implode("\n\n", $methods)],
                $elementQueryBehaviorFile
            );

            static::_writeFieldAttributesFile(
                static::$app->getPath()->getAppPath().'/behaviors/ElementQueryTrait.php.template',
                ['{VERSION}', '{METHODS}'],
                [$storedFieldVersion, implode("\n", $methodDocs)],
                $elementQueryTraitFile
            );
        }
    }

    /**
     * Determines if a field attribute file is valid.
     *
     * @param $path
     * @param $storedFieldVersion
     *
     * @return boolean
     */
    private static function _isFieldAttributesFileValid($path, $storedFieldVersion)
    {
        if (file_exists($path)) {
            // Make sure it's up-to-date
            $f = fopen($path, 'r');
            $line = fgets($f);
            fclose($f);

            if (preg_match('/\/\/ v([a-zA-Z0-9]{12})/', $line, $matches)) {
                if ($matches[1] == $storedFieldVersion) {
                    include($path);

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Writes a field attributes file.
     *
     * @param $templatePath
     * @param $search
     * @param $replace
     * @param $destinationPath
     */
    private static function _writeFieldAttributesFile($templatePath, $search, $replace, $destinationPath)
    {
        $fileContents = Io::getFileContents($templatePath);
        $fileContents = str_replace($search, $replace, $fileContents);
        Io::writeToFile($destinationPath, $fileContents);
        include($destinationPath);
    }
}

spl_autoload_register(['Craft', 'autoload'], true, true);
