<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m190109_172845_fix_colspan migration.
 */
class m190109_172845_fix_colspan extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if ($this->db->getIsPgsql()) {
            // Manually construct the SQL for Postgres
            // (see https://github.com/yiisoft/yii2/issues/12077)
            $this->execute('alter table {{%widgets}} alter column [[colspan]] drop default, alter column [[colspan]] drop not null, alter column [[colspan]] type smallint using null');
        } else {
            $this->alterColumn(Table::WIDGETS, 'colspan', $this->tinyInteger());
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190109_172845_fix_colspan cannot be reverted.\n";
        return false;
    }
}
