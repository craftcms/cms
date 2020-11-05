<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\cache;

use craft\helpers\StringHelper;
use yii\caching\FileCache as YiiFileCache;

/**
 * @inheritdoc
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.8
 */
class FileCache extends YiiFileCache
{
    /**
     * @inheritdoc
     */
    protected function getCacheFile($key)
    {
        if ($this->keyPrefix === '') {
            return parent::getCacheFile($key);
        }

        // Copied from the parent method, except the key prefix is removed from the directory names
        $originalKey = StringHelper::removeLeft($key, $this->keyPrefix);

        if ($this->directoryLevel > 0) {
            $base = $this->cachePath;

            for ($i = 0; $i < $this->directoryLevel; ++$i) {
                if (($prefix = substr($originalKey, $i + $i, 2)) !== false) {
                    $base .= DIRECTORY_SEPARATOR . $prefix;
                }
            }

            return $base . DIRECTORY_SEPARATOR . $key . $this->cacheFileSuffix;
        }

        return $this->cachePath . DIRECTORY_SEPARATOR . $key . $this->cacheFileSuffix;
    }
}
