<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\MigrationHelper;

/**
 * m150403_185142_volumes migration.
 */
class m150403_185142_volumes extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if ($this->db->tableExists('{{%assetfiles}}')) {
            // In case this was run in a previous update attempt
            $this->dropTableIfExists('{{%assets}}');

            MigrationHelper::renameTable('{{%assetfiles}}', '{{%assets}}', $this);
        }

        if ($this->db->tableExists('{{%assetsources}}')) {
            // In case this was run in a previous update attempt
            $this->execute($this->db->getQueryBuilder()->checkIntegrity(false, '', '{{%volumes}}'));
            $this->dropTableIfExists('{{%volumes}}');

            MigrationHelper::renameTable('{{%assetsources}}', '{{%volumes}}', $this);
        }

        if ($this->db->tableExists('{{%assetfolders}}')) {
            // In case this was run in a previous update attempt
            $this->dropTableIfExists('{{%volumefolders}}');

            MigrationHelper::renameTable('{{%assetfolders}}', '{{%volumefolders}}', $this);
        }

        if ($this->db->columnExists('{{%volumefolders}}', 'sourceId')) {
            MigrationHelper::renameColumn('{{%volumefolders}}', 'sourceId', 'volumeId', $this);
        }

        if (!$this->db->columnExists('{{%volumes}}', 'url')) {
            $this->addColumn('{{%volumes}}', 'url', $this->string()->after('type'));
        }

        if (!$this->db->columnExists('{{%assetindexdata}}', 'timestamp')) {
            $this->addColumn('{{%assetindexdata}}', 'timestamp', $this->dateTime()->after('size'));
        }

        if ($this->db->columnExists('{{%assets}}', 'sourceId')) {
            MigrationHelper::renameColumn('{{%assets}}', 'sourceId', 'volumeId', $this);
        }

        if ($this->db->columnExists('{{%assetfolders}}', 'sourceId')) {
            MigrationHelper::renameColumn('{{%assetfolders}}', 'sourceId', 'volumeId', $this);
        }

        if ($this->db->columnExists('{{%assetindexdata}}', 'sourceId')) {
            MigrationHelper::renameColumn('{{%assetindexdata}}', 'sourceId', 'volumeId', $this);
        }

        if ($this->db->columnExists('{{%assettransformindex}}', 'sourceId')) {
            MigrationHelper::renameColumn('{{%assettransformindex}}', 'sourceId', 'volumeId', $this);
        }

        // Update permissions
        $permissions = (new Query())
            ->select(['id', 'name'])
            ->from(['{{%userpermissions}}'])
            ->where(['like', 'name', 'assetsource'])
            ->all($this->db);

        foreach ($permissions as $permission) {
            $newName = str_replace('assetsource', 'volume', $permission['name']);
            $this->update('{{%userpermissions}}', ['name' => $newName],
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
