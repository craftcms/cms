<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\cache;

use craft\db\Connection;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use CraftCms\Cms\Shared\Exceptions\NotSupportedException;
use Exception;
use Illuminate\Support\Facades\Log;
use PDO;
use Throwable;
use yii\base\InvalidConfigException;
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
    protected function setValue($key, $value, $duration): bool
    {
        try {
            // Make sure the table exists
            $table = $this->db->getTableSchema($this->cacheTable);
            if (!$table) {
                throw new InvalidConfigException(sprintf(
                    'The `%s` table doesn’t exist. Run the `setup/db-cache-table` command to create it.',
                    $this->db->getSchema()->getRawTableName($this->cacheTable),
                ));
            }

            // Make sure the data fits within the column
            $maxSize = Db::getTextualColumnStorageCapacity(
                $table->getColumn('data')->dbType,
                $this->db instanceof Connection ? $this->db : null,
            );
            $valueSize = strlen($value);
            if ($maxSize && $valueSize > $maxSize) {
                throw new NotSupportedException(sprintf(
                    'The `%s`.`data` column can only store up to %s bytes. (Attempting to store %s bytes.)',
                    $this->db->getSchema()->getRawTableName($this->cacheTable),
                    $maxSize,
                    $valueSize,
                ));
            }

            // Copied from yii\caching\DbCache::setValue() except for the added includeAuditColumns=false argument
            $this->db->noCache(function(Connection $db) use ($key, $value, $duration) {
                Db::upsert($this->cacheTable, [
                    'id' => $key,
                    'expire' => $duration > 0 ? DateTimeHelper::currentTimeStamp() + $duration : 0,
                    'data' => new PdoValue($value, PDO::PARAM_LOB),
                ], db: $db);
            });
            $this->gc();
            return true;
        } catch (Throwable $e) {
            Log::warning("Unable to update or insert cache data: {$e->getMessage()}", [__METHOD__]);
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    protected function addValue($key, $value, $duration): bool
    {
        $this->gc();

        try {
            \Illuminate\Support\Facades\DB::table($this->cacheTable)->insert([
                'id' => $key,
                'expire' => $duration > 0 ? now()->getTimestamp() + $duration : 0,
                'data' => new PdoValue($value, PDO::PARAM_LOB),
            ]);
            return true;
        } catch (Exception $e) {
            Log::warning("Unable to insert cache data: {$e->getMessage()}", [__METHOD__]);
            return false;
        }
    }
}
