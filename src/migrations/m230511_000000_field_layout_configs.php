<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;

/**
 * m230511_000000_field_layout_configs migration.
 */
class m230511_000000_field_layout_configs extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if ($this->db->columnExists(Table::FIELDLAYOUTS, 'config')) {
            return true;
        }

        // Fetch the field layouts *before* adding the column, so the fields service knows to look in the old tables
        $fieldsService = Craft::$app->getFields();
        $fieldLayouts = $fieldsService->getAllLayouts();

        $this->addColumn(Table::FIELDLAYOUTS, 'config', $this->text()->after('type'));
        $this->db->getSchema()->refreshTableSchema(Table::FIELDLAYOUTS);

        foreach ($fieldLayouts as $fieldLayout) {
            $fieldsService->saveLayout($fieldLayout, false);
        }

        $this->dropAllForeignKeysToTable('{{%fieldlayoutfields}}');
        $this->dropAllForeignKeysToTable('{{%fieldlayouttabs}}');

        $this->dropTableIfExists('{{%fieldlayoutfields}}');
        $this->dropTableIfExists('{{%fieldlayouttabs}}');

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m230511_000000_field_layout_configs cannot be reverted.\n";
        return false;
    }
}
