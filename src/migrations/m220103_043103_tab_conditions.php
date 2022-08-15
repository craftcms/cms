<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m220103_043103_tab_conditions migration.
 */
class m220103_043103_tab_conditions extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->addColumn(Table::FIELDLAYOUTTABS, 'settings', $this->text()->after('name'));
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropColumn(Table::FIELDLAYOUTTABS, 'settings');
        return true;
    }
}
