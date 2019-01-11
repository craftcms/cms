<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\MigrationHelper;

/**
 * m190110_214819_soft_delete_volumes migration.
 */
class m190110_214819_soft_delete_volumes extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Add the dateDeleted column
        $this->addColumn('{{%volumes}}', 'dateDeleted', $this->dateTime()->null()->after('dateUpdated'));
        $this->createIndex(null, '{{%volumes}}', ['dateDeleted'], false);

        // Unique volume names & handles should no longer be enforced by the DB
        MigrationHelper::dropIndexIfExists('{{%volumes}}', ['name'], true, $this);
        MigrationHelper::dropIndexIfExists('{{%volumes}}', ['handle'], true, $this);
        $this->createIndex(null, '{{%volumes}}', ['handle'], false);

        // Give assets a way to remember whether their file was kept on delete
        $this->addColumn('{{%assets}}', 'keptFile', $this->boolean()->null()->after('focalPoint'));
        $this->createIndex(null, '{{%assets}}', ['volumeId', 'keptFile'], false);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190110_214819_soft_delete_volumes cannot be reverted.\n";
        return false;
    }
}
