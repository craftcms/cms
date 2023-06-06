<?php

namespace craft\migrations;

use craft\db\Migration;

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
        $this->addColumn('{{%fieldlayouttabs}}', 'settings', $this->text()->after('name'));
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropColumn('{{%fieldlayouttabs}}', 'settings');
        return true;
    }
}
