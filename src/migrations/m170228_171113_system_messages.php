<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;
use craft\helpers\MigrationHelper;

/**
 * m170228_171113_system_messages migration.
 */
class m170228_171113_system_messages extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // In case this was run in a previous update attempt
        $this->dropTableIfExists(Table::SYSTEMMESSAGES);

        MigrationHelper::renameTable('{{%emailmessages}}', Table::SYSTEMMESSAGES);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m170228_171113_system_messages cannot be reverted.\n";

        return false;
    }
}
