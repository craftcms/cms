<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m240731_053543_soft_delete_fields migration.
 */
class m240731_053543_soft_delete_fields extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->addColumn(Table::FIELDS, 'dateDeleted', $this->dateTime()->null()->after('dateUpdated'));
        $this->createIndex(null, Table::FIELDS, ['dateDeleted'], false);
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m240731_053543_soft_delete_fields cannot be reverted.\n";
        return false;
    }
}
