<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\mutex;

use Craft;
use craft\helpers\FileHelper;

/**
 * @inheritdoc
 * @see    Mutex
 * @todo   Remove this class when Yii 2.0.11 comes out
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class FileMutex extends \yii\mutex\FileMutex
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->mutexPath = Craft::getAlias($this->mutexPath);
        if (!is_dir($this->mutexPath)) {
            FileHelper::createDirectory($this->mutexPath, $this->dirMode, true);
        }
    }

    /**
     * @inheritdoc
     */
    protected function getLockFilePath($name)
    {
        return $this->mutexPath.DIRECTORY_SEPARATOR.md5($name).'.lock';
    }
}
