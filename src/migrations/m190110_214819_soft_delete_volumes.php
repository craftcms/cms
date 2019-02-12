<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;
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
        $this->addColumn(Table::VOLUMES, 'dateDeleted', $this->dateTime()->null()->after('dateUpdated'));
        $this->createIndex(null, Table::VOLUMES, ['dateDeleted'], false);

        // Unique volume names & handles should no longer be enforced by the DB
        MigrationHelper::dropIndexIfExists(Table::VOLUMES, ['name'], true, $this);
        MigrationHelper::dropIndexIfExists(Table::VOLUMES, ['handle'], true, $this);
        $this->createIndex(null, Table::VOLUMES, ['name'], false);
        $this->createIndex(null, Table::VOLUMES, ['handle'], false);

        // Give assets a way to remember whether their file was kept on delete
        $this->addColumn(Table::ASSETS, 'keptFile', $this->boolean()->null()->after('focalPoint'));
        $this->createIndex(null, Table::ASSETS, ['volumeId', 'keptFile'], false);
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
