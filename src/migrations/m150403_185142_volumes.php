<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\MigrationHelper;

/**
 * m150403_185142_volumes migration.
 */
class m150403_185142_volumes extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if ($this->db->tableExists('{{%assetfiles}}')) {
            // In case this was run in a previous update attempt
            $this->dropTableIfExists(Table::ASSETS);

            MigrationHelper::renameTable('{{%assetfiles}}', Table::ASSETS, $this);
        }

        if ($this->db->tableExists('{{%assetsources}}')) {
            // In case this was run in a previous update attempt
            $this->execute($this->db->getQueryBuilder()->checkIntegrity(false, '', Table::VOLUMES));
            $this->dropTableIfExists(Table::VOLUMES);

            MigrationHelper::renameTable('{{%assetsources}}', Table::VOLUMES, $this);
        }

        if ($this->db->tableExists('{{%assetfolders}}')) {
            // In case this was run in a previous update attempt
            $this->dropTableIfExists(Table::VOLUMEFOLDERS);

            MigrationHelper::renameTable('{{%assetfolders}}', Table::VOLUMEFOLDERS, $this);
        }

        if ($this->db->columnExists(Table::VOLUMEFOLDERS, 'sourceId')) {
            MigrationHelper::renameColumn(Table::VOLUMEFOLDERS, 'sourceId', 'volumeId', $this);
        }

        if (!$this->db->columnExists(Table::VOLUMES, 'url')) {
            $this->addColumn(Table::VOLUMES, 'url', $this->string()->after('type'));
        }

        if (!$this->db->columnExists(Table::ASSETINDEXDATA, 'timestamp')) {
            $this->addColumn(Table::ASSETINDEXDATA, 'timestamp', $this->dateTime()->after('size'));
        }

        if ($this->db->columnExists(Table::ASSETS, 'sourceId')) {
            MigrationHelper::renameColumn(Table::ASSETS, 'sourceId', 'volumeId', $this);
        }

        if ($this->db->columnExists('{{%assetfolders}}', 'sourceId')) {
            MigrationHelper::renameColumn('{{%assetfolders}}', 'sourceId', 'volumeId', $this);
        }

        if ($this->db->columnExists(Table::ASSETINDEXDATA, 'sourceId')) {
            MigrationHelper::renameColumn(Table::ASSETINDEXDATA, 'sourceId', 'volumeId', $this);
        }

        if ($this->db->columnExists(Table::ASSETTRANSFORMINDEX, 'sourceId')) {
            MigrationHelper::renameColumn(Table::ASSETTRANSFORMINDEX, 'sourceId', 'volumeId', $this);
        }

        // Update permissions
        $permissions = (new Query())
            ->select(['id', 'name'])
            ->from([Table::USERPERMISSIONS])
            ->where(['like', 'name', 'assetsource'])
            ->all($this->db);

        foreach ($permissions as $permission) {
            $newName = str_replace('assetsource', 'volume', $permission['name']);
            $this->update(Table::USERPERMISSIONS, ['name' => $newName],
                ['id' => $permission['id']], [], false);
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m150403_185142_volumes cannot be reverted.\n";

        return false;
    }
}
