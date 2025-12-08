<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m251208_193926_enabledByOwner migration.
 */
class m251208_193926_enabledByOwner extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->addColumn(Table::ELEMENTS_SITES, 'enabledByOwner', $this->boolean()->notNull()->defaultValue(true)->after('enabled'));
        $this->createIndex(null, Table::ELEMENTS_SITES, ['enabledByOwner'], false);
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        if ($this->db->columnExists(Table::ELEMENTS_SITES, 'enabledByOwner')) {
            $this->dropIndexIfExists(Table::ELEMENTS_SITES, 'enabledByOwner');
            $this->dropColumn(Table::ELEMENTS_SITES, 'enabledByOwner');
        }

        return true;
    }
}
