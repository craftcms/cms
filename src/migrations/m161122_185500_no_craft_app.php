<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m161122_185500_no_craft_app migration.
 */
class m161122_185500_no_craft_app extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $tables = [
            Table::ELEMENTINDEXSETTINGS,
            Table::ELEMENTS,
            Table::FIELDLAYOUTS,
            Table::FIELDS,
            Table::TEMPLATECACHEQUERIES,
            Table::VOLUMES,
            Table::WIDGETS,
        ];

        foreach ($tables as $table) {
            $this->replace($table, 'type', 'craft\\app\\', 'craft\\');
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m161122_185500_no_craft_app cannot be reverted.\n";

        return false;
    }
}
