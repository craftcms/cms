<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\helpers\MigrationHelper;

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
        $this->addColumn('{{%elements}}', 'dateDeleted', $this->dateTime()->null()->after('dateUpdated'));
        $this->createIndex(null, '{{%elements}}', ['dateDeleted'], false);

        // Give categories and Structure section entries a way to keep track of their parent IDs
        // in case they are soft-deleted and need to be restored
        $this->addColumn('{{%categories}}', 'parentId', $this->integer()->after('groupId'));
        $this->addColumn('{{%entries}}', 'parentId', $this->integer()->after('sectionId'));
        $this->addForeignKey(null, '{{%categories}}', ['parentId'], '{{%categories}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, '{{%entries}}', ['parentId'], '{{%entries}}', ['id'], 'SET NULL', null);

        // Unique element URIs should no longer be enforced by the DB
        MigrationHelper::dropIndexIfExists('{{%elements_sites}}', ['uri', 'siteId'], true, $this);
        $this->createIndex(null, '{{%elements_sites}}', ['uri', 'siteId'], false);
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
