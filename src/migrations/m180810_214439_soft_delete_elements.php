<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m180810_214439_soft_delete_elements migration.
 */
class m180810_214439_soft_delete_elements extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Add the dateDeleted column
        $this->addColumn(Table::ELEMENTS, 'dateDeleted', $this->dateTime()->null()->after('dateUpdated'));
        $this->createIndex(null, Table::ELEMENTS, ['dateDeleted'], false);

        // Give categories and Structure section entries a way to keep track of their parent IDs
        // in case they are soft-deleted and need to be restored
        $this->addColumn(Table::CATEGORIES, 'parentId', $this->integer()->after('groupId'));
        $this->addColumn(Table::ENTRIES, 'parentId', $this->integer()->after('sectionId'));
        $this->addForeignKey(null, Table::CATEGORIES, ['parentId'], Table::CATEGORIES, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::ENTRIES, ['parentId'], Table::ENTRIES, ['id'], 'SET NULL', null);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180810_214439_soft_delete_elements cannot be reverted.\n";
        return false;
    }
}
