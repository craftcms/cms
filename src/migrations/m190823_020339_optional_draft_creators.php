<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;
use craft\helpers\MigrationHelper;

/**
 * m190823_020339_optional_draft_creators migration.
 */
class m190823_020339_optional_draft_creators extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if ($this->db->getIsPgsql()) {
            $this->execute('alter table ' . Table::DRAFTS . ' alter column [[creatorId]] drop not null');
        } else {
            $this->alterColumn(Table::DRAFTS, 'creatorId', $this->integer());
        }

        MigrationHelper::dropForeignKeyIfExists(Table::DRAFTS, ['creatorId'], $this);
        MigrationHelper::dropForeignKeyIfExists(Table::REVISIONS, ['creatorId'], $this);
        $this->addForeignKey(null, Table::DRAFTS, ['creatorId'], Table::USERS, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::REVISIONS, ['creatorId'], Table::USERS, ['id'], 'SET NULL', null);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190823_020339_optional_draft_creators cannot be reverted.\n";
        return false;
    }
}
