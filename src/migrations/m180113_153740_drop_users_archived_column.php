<?php

namespace craft\migrations;

use craft\db\Migration;

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
        $this->dropColumn('{{%users}}', 'archived');
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
