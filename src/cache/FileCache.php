<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\cache;

use Craft;
use craft\app\helpers\Io;

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
     * Stores a value identified by a key in cache. This is the implementation of the method declared in the parent
     * class.
     *
     * @param string  $key    The key identifying the value to be cached
     * @param string  $value  The value to be cached
     * @param integer $expire The number of seconds in which the cached value will expire. 0 means never expire.
     *
     * @return boolean true if the value is successfully stored into cache, false otherwise.
     */
    protected function setValue($key, $value, $expire)
    {
        if (!$this->_gced && mt_rand(0, 1000000) < $this->gcProbability) {
            $this->gc();
            $this->_gced = true;
        }

        if ($expire <= 0) {
            $expire = 31536000; // 1 year
        }

        $expire += time();

        $cacheFile = $this->getCacheFile($key);

        if ($this->directoryLevel > 0) {
            Io::createFolder(Io::getFolderName($cacheFile));
        }

        if ($this->_originalKey == 'useWriteFileLock') {
            if (Io::writeToFile($cacheFile, $value, true, false,
                    true) !== false
            ) {
                Io::changePermissions($cacheFile, Craft::$app->getConfig()->get('defaultFilePermissions'));

                return Io::touch($cacheFile, $expire);
            }

            return false;
        }

        if (Io::writeToFile($cacheFile, $value) !== false) {
            Io::changePermissions($cacheFile, Craft::$app->getConfig()->get('defaultFilePermissions'));

            return Io::touch($cacheFile, $expire);
        }

        return false;
    }
}
