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
    protected function getCacheFile($normalizedKey): string
    {
        if ($this->keyPrefix === '') {
            return parent::getCacheFile($normalizedKey);
        }

        // Copied from the parent method, except the key prefix is removed from the directory names
        $originalKey = StringHelper::removeLeft($normalizedKey, $this->keyPrefix);

        if ($this->directoryLevel > 0) {
            $base = $this->cachePath;

            for ($i = 0; $i < $this->directoryLevel; ++$i) {
                if (($prefix = substr($originalKey, $i + $i, 2)) !== '') {
                    $base .= DIRECTORY_SEPARATOR . $prefix;
                }
            }

            return $base . DIRECTORY_SEPARATOR . $normalizedKey . $this->cacheFileSuffix;
        }

        return $this->cachePath . DIRECTORY_SEPARATOR . $normalizedKey . $this->cacheFileSuffix;
    }
}
