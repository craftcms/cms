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
        $this->alterColumn(Table::DEPRECATIONERRORS, 'traces', $this->json());
        $this->alterColumn(Table::FIELDLAYOUTS, 'config', $this->json());
        $this->alterColumn(Table::GQLSCHEMAS, 'scope', $this->json());
        $this->alterColumn(Table::SECTIONS, 'previewTargets', $this->json());
        $this->alterColumn(Table::USERPREFERENCES, 'preferences', $this->json());
        $this->alterColumn(Table::WIDGETS, 'settings', $this->json());

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
