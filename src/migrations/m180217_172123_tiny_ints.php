<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m180217_172123_tiny_ints migration.
 */
class m180217_172123_tiny_ints extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->alterColumn(Table::TOKENS, 'usageLimit', $this->tinyInteger()->unsigned());
        $this->alterColumn(Table::TOKENS, 'usageCount', $this->tinyInteger()->unsigned());
        $this->alterColumn(Table::USERS, 'invalidLoginCount', $this->tinyInteger()->unsigned());

        if ($this->db->getIsPgsql()) {
            $this->execute('alter table {{%info}} alter column [[edition]] type smallint, alter column [[edition]] set not null');
        } else {
            $this->alterColumn(Table::INFO, 'edition', $this->tinyInteger()->unsigned()->notNull());
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180217_172123_tiny_ints cannot be reverted.\n";
        return false;
    }
}
