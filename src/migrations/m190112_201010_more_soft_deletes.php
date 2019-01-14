<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
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
        $this->addColumn('{{%categorygroups}}', 'dateDeleted', $this->dateTime()->null()->after('dateUpdated'));
        $this->addColumn('{{%entrytypes}}', 'dateDeleted', $this->dateTime()->null()->after('dateUpdated'));
        $this->addColumn('{{%sections}}', 'dateDeleted', $this->dateTime()->null()->after('dateUpdated'));
        $this->addColumn('{{%structures}}', 'dateDeleted', $this->dateTime()->null()->after('dateUpdated'));
        $this->addColumn('{{%taggroups}}', 'dateDeleted', $this->dateTime()->null()->after('dateUpdated'));
        $this->createIndex(null, '{{%categorygroups}}', ['dateDeleted'], false);
        $this->createIndex(null, '{{%entrytypes}}', ['dateDeleted'], false);
        $this->createIndex(null, '{{%sections}}', ['dateDeleted'], false);
        $this->createIndex(null, '{{%structures}}', ['dateDeleted'], false);
        $this->createIndex(null, '{{%taggroups}}', ['dateDeleted'], false);

        // Unique names & handles should no longer be enforced by the DB
        MigrationHelper::dropIndexIfExists('{{%categorygroups}}', ['name'], true, $this);
        MigrationHelper::dropIndexIfExists('{{%categorygroups}}', ['handle'], true, $this);
        MigrationHelper::dropIndexIfExists('{{%entrytypes}}', ['name', 'sectionId'], true, $this);
        MigrationHelper::dropIndexIfExists('{{%entrytypes}}', ['handle', 'sectionId'], true, $this);
        MigrationHelper::dropIndexIfExists('{{%sections}}', ['name'], true, $this);
        MigrationHelper::dropIndexIfExists('{{%sections}}', ['handle'], true, $this);
        MigrationHelper::dropIndexIfExists('{{%taggroups}}', ['name'], true, $this);
        MigrationHelper::dropIndexIfExists('{{%taggroups}}', ['handle'], true, $this);
        $this->createIndex(null, '{{%categorygroups}}', ['name'], false);
        $this->createIndex(null, '{{%categorygroups}}', ['handle'], false);
        $this->createIndex(null, '{{%entrytypes}}', ['name', 'sectionId'], false);
        $this->createIndex(null, '{{%entrytypes}}', ['handle', 'sectionId'], false);
        $this->createIndex(null, '{{%sections}}', ['name'], false);
        $this->createIndex(null, '{{%sections}}', ['handle'], false);
        $this->createIndex(null, '{{%taggroups}}', ['name'], false);
        $this->createIndex(null, '{{%taggroups}}', ['handle'], false);

        // Keep track of how elements are deleted
        $this->addColumn('{{%assets}}', 'deletedWithVolume', $this->boolean()->null()->after('focalPoint'));
        $this->addColumn('{{%categories}}', 'deletedWithGroup', $this->boolean()->null()->after('parentId'));
        $this->addColumn('{{%entries}}', 'deletedWithEntryType', $this->boolean()->null()->after('expiryDate'));
        $this->addColumn('{{%matrixblocks}}', 'deletedWithOwner', $this->boolean()->null()->after('sortOrder'));
        $this->addColumn('{{%tags}}', 'deletedWithGroup', $this->boolean()->null()->after('groupId'));
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
