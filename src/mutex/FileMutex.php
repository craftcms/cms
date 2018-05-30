<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\mutex;

use Craft;
use craft\helpers\FileHelper;
use yii\base\Exception;

/**
 * @inheritdoc
 * @see Mutex
 * @todo Remove this class when Yii 2.0.11 comes out
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FileMutex extends \yii\mutex\FileMutex
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $mutexPath = Craft::getAlias($this->mutexPath);

        if ($mutexPath === false) {
            throw new Exception('There was a problem getting the mutex path.');
        }
        $this->mutexPath = $mutexPath;
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
