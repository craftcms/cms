<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\utilities;

use Craft;
use craft\base\Utility;
use craft\config\DbConfig;
use craft\helpers\App;
use GuzzleHttp\Client;
use Imagine\Gd\Imagine;
use PDO;
use RequirementsChecker;
use Twig_Environment;
use Yii;

/**
 * SystemReport represents a SystemReport dashboard widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class SystemReport extends Utility
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'System Report');
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return 'system-report';
    }

    /**
     * @inheritdoc
     */
    public static function iconPath()
    {
        return Craft::getAlias('@app/icons/check.svg');
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('_components/utilities/SystemReport', [
            'appInfo' => self::_appInfo(),
            'plugins' => Craft::$app->getPlugins()->getAllPlugins(),
            'requirements' => self::_requirementResults(),
        ]);
    }

    /**
     * Returns application info.
     *
     * @return array
     */
    private static function _appInfo(): array
    {
        return [
            'PHP version' => PHP_VERSION,
            'Database driver & version' => self::_dbDriver(),
            'Image driver & version' => self::_imageDriver(),
            'Craft edition & version' => 'Craft '.App::editionName(Craft::$app->getEdition()).' '.Craft::$app->version,
            'Yii version' => Yii::getVersion(),
            'Twig version' => Twig_Environment::VERSION,
            'Guzzle version' => Client::VERSION,
            'Imagine version' => Imagine::VERSION,
        ];
    }

    /**
     * Returns the DB driver name and version
     *
     * @return string
     */
    private static function _dbDriver(): string
    {
        $db = Craft::$app->getDb();
        $driverName = $db->getDriverName();

        switch ($driverName) {
            case DbConfig::DRIVER_MYSQL:
                $driverName = 'MySQL';
                break;
            case DbConfig::DRIVER_PGSQL:
                $driverName = 'PostgreSQL';
                break;
        }

        return $driverName.' '.$db->getMasterPdo()->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    /**
     * Returns the image driver name and version
     *
     * @return string
     */
    private static function _imageDriver(): string
    {
        $imagesService = Craft::$app->getImages();

        if ($imagesService->getIsGd()) {
            return 'GD '.phpversion('gd');
        }

        return 'Imagick '.phpversion('imagick').', ImageMagick '.$imagesService->getImageMagickApiVersion();
    }

    /**
     * Runs the requirements checker and returns its results.
     *
     * @return array
     */
    private static function _requirementResults(): array
    {
        $reqCheck = new RequirementsChecker();
        $reqCheck->checkCraft();

        return $reqCheck->getResult()['requirements'];
    }
}
