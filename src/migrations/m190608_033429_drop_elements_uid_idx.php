<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;
use craft\helpers\MigrationHelper;

/**
 * m190608_033429_drop_elements_uid_idx migration.
 */
class m190608_033429_drop_elements_uid_idx extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        MigrationHelper::dropIndexIfExists(Table::ELEMENTS, ['uid'], true, $this);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190608_033429_drop_elements_uid_idx cannot be reverted.\n";
        return false;
    }
}
