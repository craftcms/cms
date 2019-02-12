<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m180113_153740_drop_users_archived_column migration.
 */
class m180113_153740_drop_users_archived_column extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->dropColumn(Table::USERS, 'archived');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180113_153740_drop_users_archived_column cannot be reverted.\n";
        return false;
    }
}
