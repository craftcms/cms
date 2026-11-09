<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db\sqlite;

use craft\db\Connection;

/**
 * @inheritdoc
 * @property Connection $db Connection the DB connection that this command is associated with.
 * @since 6.0.0
 */
class QueryBuilder extends \yii\db\sqlite\QueryBuilder
{
    /**
     * Builds a SQL statement for dropping a DB table if it exists.
     *
     * @param string $table The table to be dropped. The name will be properly quoted by the method.
     * @return string The SQL statement for dropping a DB table.
     */
    public function dropTableIfExists(string $table): string
    {
        return 'DROP TABLE IF EXISTS ' . $this->db->quoteTableName($table);
    }
}
