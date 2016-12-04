<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\cache;

use Craft;
use craft\helpers\FileHelper;
use craft\helpers\Io;
use yii\base\ErrorException;

/**
 * Class FileCache
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class FileCache extends \yii\caching\FileCache
{
    // Properties
    // =========================================================================

    /**
     * @var bool
     */
    private $_gced = false;

    /**
     * @var
     */
    private $_originalKey;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function set($id, $value, $expire = null, $dependency = null)
    {
        $this->_originalKey = $id;

        return parent::set($id, $value, $expire, $dependency);
    }

    /**
     * @inheritdoc
     */
    public function add($id, $value, $expire = null, $dependency = null)
    {
        $this->_originalKey = $id;

        return parent::add($id, $value, $expire, $dependency);
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function setValue($key, $value, $duration)
    {
        if (!$this->_gced && mt_rand(0, 1000000) < $this->gcProbability) {
            $this->gc();
            $this->_gced = true;
        }

        if ($duration <= 0) {
            $duration = 31536000; // 1 year
        }

        $cacheFile = $this->getCacheFile($key);

        if ($this->directoryLevel > 0) {
            $folder = pathinfo($cacheFile, PATHINFO_DIRNAME);
            if (!is_dir($folder)) {
                FileHelper::createDirectory($folder);
            }
        }

        try {
            FileHelper::writeToFile($cacheFile, $value);

            if ($this->fileMode !== null) {
                @chmod($cacheFile, $this->fileMode);
            }

            return @touch($cacheFile, $duration + time());
        } catch (ErrorException $e) {
            Craft::warning("Unable to write cache file '{$cacheFile}': ".$e->getMessage(), __METHOD__);

            return false;
        }
    }
}
