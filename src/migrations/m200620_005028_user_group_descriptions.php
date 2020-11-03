<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m200620_005028_user_group_descriptions migration.
 */
class m200620_005028_user_group_descriptions extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn(Table::USERGROUPS, 'description', $this->text()->after('handle'));
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200620_005028_user_group_descriptions cannot be reverted.\n";
        return false;
    }
}
