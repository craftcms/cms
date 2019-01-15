<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;
use craft\helpers\MigrationHelper;

/**
 * m190112_201010_more_soft_deletes migration.
 */
class m190112_201010_more_soft_deletes extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Add the dateDeleted columns
        $this->addColumn(Table::CATEGORYGROUPS, 'dateDeleted', $this->dateTime()->null()->after('dateUpdated'));
        $this->addColumn(Table::ENTRYTYPES, 'dateDeleted', $this->dateTime()->null()->after('dateUpdated'));
        $this->addColumn(Table::SECTIONS, 'dateDeleted', $this->dateTime()->null()->after('dateUpdated'));
        $this->addColumn(Table::STRUCTURES, 'dateDeleted', $this->dateTime()->null()->after('dateUpdated'));
        $this->addColumn(Table::TAGGROUPS, 'dateDeleted', $this->dateTime()->null()->after('dateUpdated'));
        $this->createIndex(null, Table::CATEGORYGROUPS, ['dateDeleted'], false);
        $this->createIndex(null, Table::ENTRYTYPES, ['dateDeleted'], false);
        $this->createIndex(null, Table::SECTIONS, ['dateDeleted'], false);
        $this->createIndex(null, Table::STRUCTURES, ['dateDeleted'], false);
        $this->createIndex(null, Table::TAGGROUPS, ['dateDeleted'], false);

        // Unique names & handles should no longer be enforced by the DB
        MigrationHelper::dropIndexIfExists(Table::CATEGORYGROUPS, ['name'], true, $this);
        MigrationHelper::dropIndexIfExists(Table::CATEGORYGROUPS, ['handle'], true, $this);
        MigrationHelper::dropIndexIfExists(Table::ENTRYTYPES, ['name', 'sectionId'], true, $this);
        MigrationHelper::dropIndexIfExists(Table::ENTRYTYPES, ['handle', 'sectionId'], true, $this);
        MigrationHelper::dropIndexIfExists(Table::SECTIONS, ['name'], true, $this);
        MigrationHelper::dropIndexIfExists(Table::SECTIONS, ['handle'], true, $this);
        MigrationHelper::dropIndexIfExists(Table::TAGGROUPS, ['name'], true, $this);
        MigrationHelper::dropIndexIfExists(Table::TAGGROUPS, ['handle'], true, $this);
        $this->createIndex(null, Table::CATEGORYGROUPS, ['name'], false);
        $this->createIndex(null, Table::CATEGORYGROUPS, ['handle'], false);
        $this->createIndex(null, Table::ENTRYTYPES, ['name', 'sectionId'], false);
        $this->createIndex(null, Table::ENTRYTYPES, ['handle', 'sectionId'], false);
        $this->createIndex(null, Table::SECTIONS, ['name'], false);
        $this->createIndex(null, Table::SECTIONS, ['handle'], false);
        $this->createIndex(null, Table::TAGGROUPS, ['name'], false);
        $this->createIndex(null, Table::TAGGROUPS, ['handle'], false);

        // Keep track of how elements are deleted
        $this->addColumn(Table::ASSETS, 'deletedWithVolume', $this->boolean()->null()->after('focalPoint'));
        $this->addColumn(Table::CATEGORIES, 'deletedWithGroup', $this->boolean()->null()->after('parentId'));
        $this->addColumn(Table::ENTRIES, 'deletedWithEntryType', $this->boolean()->null()->after('expiryDate'));
        $this->addColumn(Table::MATRIXBLOCKS, 'deletedWithOwner', $this->boolean()->null()->after('sortOrder'));
        $this->addColumn(Table::TAGS, 'deletedWithGroup', $this->boolean()->null()->after('groupId'));
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190112_201010_more_soft_deletes cannot be reverted.\n";
        return false;
    }
}
