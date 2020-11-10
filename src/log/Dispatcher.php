<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\log;

use Craft;
use yii\i18n\PhpMessageSource;
use yii\log\Logger;

/**
 * Class Dispatcher
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.0
 */
class Dispatcher extends \yii\log\Dispatcher
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $isConsoleRequest = Craft::$app->getRequest()->getIsConsoleRequest();
        if ($isConsoleRequest || Craft::$app->getUser()->enableSession) {
            $generalConfig = Craft::$app->getConfig()->getGeneral();

            $targetConfig = [
                'class' => FileTarget::class,
                'fileMode' => $generalConfig->defaultFileMode,
                'dirMode' => $generalConfig->defaultDirMode,
                'includeUserIp' => $generalConfig->storeUserIps,
                'except' => [
                    PhpMessageSource::class . ':*',
                ],
            ];

            if ($isConsoleRequest) {
                $targetConfig['logFile'] = '@storage/logs/console.log';
            } else {
                $targetConfig['logFile'] = '@storage/logs/web.log';
            }

            // Only log errors and warnings, unless Craft is running in Dev Mode or it's being installed/updated
            // (Explicitly check GeneralConfig::$devMode here, because YII_DEBUG is always `1` for console requests.)
            if (!$generalConfig->devMode && Craft::$app->getIsInstalled() && !Craft::$app->getUpdates()->getIsCraftDbMigrationNeeded()) {
                $targetConfig['levels'] = Logger::LEVEL_ERROR | Logger::LEVEL_WARNING;
            }

            $this->targets['__craft'] = Craft::createObject($targetConfig);
        }
    }
}
