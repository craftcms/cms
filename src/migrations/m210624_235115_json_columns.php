<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m210624_235115_json_columns migration.
 */
class m210624_235115_json_columns extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // Ensure there are no active tokens (which could have contained invalid JSON)
        $this->truncateTable(Table::TOKENS);

        $this->convertColumnToJson(Table::DEPRECATIONERRORS, 'traces');
        $this->convertColumnToJson(Table::ELEMENTINDEXSETTINGS, 'settings');
        $this->convertColumnToJson(Table::FIELDLAYOUTTABS, 'elements');
        $this->convertColumnToJson(Table::FIELDS, 'settings');
        $this->convertColumnToJson(Table::GQLSCHEMAS, 'scope');
        $this->convertColumnToJson(Table::SECTIONS, 'previewTargets');
        $this->convertColumnToJson(Table::TOKENS, 'route');
        $this->convertColumnToJson(Table::USERPREFERENCES, 'preferences');
        $this->convertColumnToJson(Table::VOLUMES, 'settings');
        $this->convertColumnToJson(Table::WIDGETS, 'settings');

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m210624_235115_json_columns cannot be reverted.\n";
        return false;
    }
}
