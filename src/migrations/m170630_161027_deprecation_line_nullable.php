<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m170630_161027_deprecation_line_nullable migration.
 */
class m170630_161027_deprecation_line_nullable extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if ($this->db->getIsPgsql()) {
            // Manually construct the SQL for Postgres
            // (see https://github.com/yiisoft/yii2/issues/12077)
            $this->execute('alter table ' . Table::DEPRECATIONERRORS . ' alter column [[line]] type smallint, alter column [[line]] drop not null');
        } else {
            $this->alterColumn(Table::DEPRECATIONERRORS, 'line', $this->smallInteger()->unsigned());
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m170630_161027_deprecation_line_nullable cannot be reverted.\n";
        return false;
    }
}
