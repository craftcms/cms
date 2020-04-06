<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\cache;

use Craft;
use craft\db\Connection;
use yii\caching\DbCache as YiiDbCache;
use yii\db\PdoValue;

/**
 * @inheritdoc
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.14
 */
class DbCache extends YiiDbCache
{
    /**
     * @inheritdoc
     */
    protected function setValue($key, $value, $duration)
    {
        // Copied from yii\caching\DbCache::setValue() except for the added includeAuditColumns=false argument
        try {
            $this->db->noCache(function(Connection $db) use ($key, $value, $duration) {
                $db->createCommand()
                    ->upsert($this->cacheTable, [
                        'id' => $key,
                        'expire' => $duration > 0 ? $duration + time() : 0,
                        'data' => new PdoValue($value, \PDO::PARAM_LOB),
                    ], true, [], false)
                    ->execute();
            });
            $this->gc();
            return true;
        } catch (\Exception $e) {
            Craft::warning("Unable to update or insert cache data: {$e->getMessage()}", __METHOD__);
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    protected function addValue($key, $value, $duration)
    {
        $this->gc();

        try {
            $this->db->noCache(function (Connection $db) use ($key, $value, $duration) {
                $db->createCommand()
                    ->insert($this->cacheTable, [
                        'id' => $key,
                        'expire' => $duration > 0 ? $duration + time() : 0,
                        'data' => new PdoValue($value, \PDO::PARAM_LOB),
                    ], false)
                    ->execute();
            });
            return true;
        } catch (\Exception $e) {
            Craft::warning("Unable to insert cache data: {$e->getMessage()}", __METHOD__);
            return false;
        }
    }
}
