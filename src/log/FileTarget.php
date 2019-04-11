<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\log;

use Craft;
use craft\base\LogTrait;

/**
 * Class FileTarget
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FileTarget extends \yii\log\FileTarget
{
    use LogTrait;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $isConsoleRequest = Craft::$app->getRequest()->getIsConsoleRequest();
        $this->fileMode = $generalConfig->defaultFileMode;
        $this->dirMode = $generalConfig->defaultDirMode;

        if ($isConsoleRequest) {
            $this->logFile = '@storage/logs/console.log';
        } else {
            $this->logFile = '@storage/logs/web.log';
        }
    }
}
