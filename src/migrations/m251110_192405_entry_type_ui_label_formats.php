<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m251110_192405_entry_type_ui_label_formats migration.
 */
class m251110_192405_entry_type_ui_label_formats extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->addColumn(Table::ENTRYTYPES, 'uiLabelFormat', $this->string()->defaultValue('{title}')->notNull()->after('color'));
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropColumn(Table::ENTRYTYPES, 'uiLabelFormat');
        return true;
    }
}
