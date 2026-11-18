<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m261118_120000_index_addresses_by_owner migration.
 */
class m261118_120000_index_addresses_by_owner extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->hasOwnerIndex()) {
            $this->createIndex(null, Table::ADDRESSES, ['primaryOwnerId']);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        // The index may have existed before this migration or support a foreign key.
        echo "m261118_120000_index_addresses_by_owner cannot be reverted.\n";
        return false;
    }

    private function hasOwnerIndex(): bool
    {
        if ($this->db->getIsPgsql()) {
            $table = $this->db->getSchema()->getRawTableName(Table::ADDRESSES);
            $sql = <<<'SQL'
SELECT 1
FROM pg_catalog.pg_index i
JOIN pg_catalog.pg_attribute a ON a.attrelid = i.indrelid AND a.attnum = i.indkey[0]
JOIN pg_catalog.pg_opclass op ON op.oid = i.indclass[0]
JOIN pg_catalog.pg_am am ON am.oid = op.opcmethod
WHERE i.indrelid = pg_catalog.to_regclass(:table)
  AND a.attname = 'primaryOwnerId'
  AND i.indnkeyatts > 0 AND i.indpred IS NULL
  AND i.indisvalid AND i.indisready AND i.indislive
  AND op.opcdefault AND op.opcintype = a.atttypid
  AND am.amname IN ('btree', 'hash')
LIMIT 1
SQL;
            return (bool)$this->db->createCommand($sql, [':table' => $this->db->quoteTableName($table)])->queryScalar();
        }

        $sql = 'SHOW INDEX FROM ' . Table::ADDRESSES .
            " WHERE Seq_in_index = 1 AND Column_name = 'primaryOwnerId' AND Index_type = 'BTREE' AND Sub_part IS NULL";
        $rows = $this->db->createCommand($sql)->queryAll();
        foreach ($rows as $row) {
            // MySQL reports visibility and MariaDB 10.6+ reports ignored indexes.
            $row = array_change_key_case($row);
            if (($row['visible'] ?? 'YES') === 'YES' && ($row['ignored'] ?? 'NO') === 'NO') {
                return true;
            }
        }

        return false;
    }
}
