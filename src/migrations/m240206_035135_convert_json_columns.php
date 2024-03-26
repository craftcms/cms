<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m240206_035135_convert_json_columns migration.
 */
class m240206_035135_convert_json_columns extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $columns = [
            [Table::DEPRECATIONERRORS, 'traces'],
            [Table::FIELDLAYOUTS, 'config'],
            [Table::GQLSCHEMAS, 'scope'],
            [Table::SECTIONS, 'previewTargets'],
            [Table::USERPREFERENCES, 'preferences'],
            [Table::WIDGETS, 'settings'],
        ];

        foreach ($columns as [$table, $column]) {
            if ($this->db->getIsPgsql()) {
                $this->execute(<<<SQL
alter table $table alter column "$column" type jsonb using "$column"::jsonb; 
SQL);
            } else {
                $this->alterColumn($table, $column, $this->json());
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m240206_035135_convert_json_columns cannot be reverted.\n";
        return false;
    }
}
