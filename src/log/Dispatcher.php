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

        $devModeLogging = $this->_devModeLogging();
        $isConsoleRequest = Craft::$app->getRequest()->getIsConsoleRequest();

        if ($isConsoleRequest || Craft::$app->getUser()->enableSession) {
            $generalConfig = Craft::$app->getConfig()->getGeneral();

            $fileTargetConfig = [
                'class' => FileTarget::class,
                'fileMode' => $generalConfig->defaultFileMode,
                'dirMode' => $generalConfig->defaultDirMode,
                'includeUserIp' => $generalConfig->storeUserIps,
                'except' => [
                    PhpMessageSource::class . ':*',
                ],
            ];

            if ($isConsoleRequest) {
                $fileTargetConfig['logFile'] = '@storage/logs/console.log';
            } else {
                $fileTargetConfig['logFile'] = '@storage/logs/web.log';
            }

            if (!$devModeLogging) {
                $fileTargetConfig['levels'] = Logger::LEVEL_ERROR | Logger::LEVEL_WARNING;
            }

            $this->targets['__craftFileTarget'] = Craft::createObject($fileTargetConfig);

            if (defined('CRAFT_STREAM_LOG') && CRAFT_STREAM_LOG === true) {
                $streamErrLogTarget = [
                    'class' => StreamLogTarget::class,
                    'url' => 'php://stderr',
                    'levels' => Logger::LEVEL_ERROR | Logger::LEVEL_WARNING,
                    'includeUserIp' => $generalConfig->storeUserIps,
                ];

                $this->targets['__craftStreamErrTarget'] = Craft::createObject($streamErrLogTarget);

                if ($devModeLogging) {
                    $streamOutLogTarget = [
                        'class' => StreamLogTarget::class,
                        'url' => 'php://stdout',
                        'includeUserIp' => $generalConfig->storeUserIps,
                    ];

                    $this->targets['__craftStreamOutTarget'] = Craft::createObject($streamOutLogTarget);
                }
            }
        }
    }

    /**
     * @return bool
     */
    private function _devModeLogging(): bool
    {
        // Only log errors and warnings, unless Craft is running in Dev Mode or it's being installed/updated
        // (Explicitly check GeneralConfig::$devMode here, because YII_DEBUG is always `1` for console requests.)
        if (!Craft::$app->getConfig()->getGeneral()->devMode && Craft::$app->getIsInstalled() && !Craft::$app->getUpdates()->getIsCraftDbMigrationNeeded()) {
            return false;
        }

        return true;
    }
}
