<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m190523_190303_optional_revision_creators migration.
 */
class m190523_190303_optional_revision_creators extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if ($this->db->getIsPgsql()) {
            $this->execute('alter table ' . Table::REVISIONS . ' alter column [[creatorId]] drop not null');
        } else {
            $this->alterColumn(Table::REVISIONS, 'creatorId', $this->integer());
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190523_190303_optional_revision_creators cannot be reverted.\n";
        return false;
    }
}
