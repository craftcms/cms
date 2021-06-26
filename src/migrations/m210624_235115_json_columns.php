<?php

namespace craft\migrations;

use Craft;
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
    public function safeUp()
    {
        // Ensure there are no active tokens (which could have contained invalid JSON)
        $this->truncateTable(Table::TOKENS);

        $this->alterColumn(Table::DEPRECATIONERRORS, 'traces', $this->json());
        $this->alterColumn(Table::ELEMENTINDEXSETTINGS, 'settings', $this->json());
        $this->alterColumn(Table::FIELDLAYOUTTABS, 'elements', $this->json());
        $this->alterColumn(Table::FIELDS, 'settings', $this->json());
        $this->alterColumn(Table::GQLSCHEMAS, 'scope', $this->json());
        $this->alterColumn(Table::SECTIONS, 'previewTargets', $this->json());
        $this->alterColumn(Table::TOKENS, 'route', $this->json());
        $this->alterColumn(Table::USERPREFERENCES, 'preferences', $this->json());
        $this->alterColumn(Table::VOLUMES, 'settings', $this->json());
        $this->alterColumn(Table::WIDGETS, 'settings', $this->json());
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m210624_235115_json_columns cannot be reverted.\n";
        return false;
    }
}
