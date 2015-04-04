<?php

namespace craft\app\migrations;

use Craft;
use craft\app\db\Migration;
use craft\app\db\Query;
use craft\app\helpers\MigrationHelper;

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
		if ($this->db->tableExists('{{%assetfiles}}'))
		{
			MigrationHelper::renameTable('{{%assetfiles}}', '{{%assets}}', $this);
		}

		if ($this->db->tableExists('{{%assetsources}}'))
		{
			MigrationHelper::renameTable('{{%assetsources}}', '{{%volumes}}', $this);
		}

		if ($this->db->tableExists('{{%assetfolders}}'))
		{
			MigrationHelper::renameTable('{{%assetfolders}}', '{{%volumefolders}}', $this);
		}

		if ($this->db->columnExists('{{%volumefolders}}', 'sourceId'))
		{
			MigrationHelper::renameColumn('{{%volumefolders}}', 'sourceId', 'volumeId');
		}

		if (!$this->db->columnExists('{{%volumes}}', 'url'))
		{
			$this->addColumnAfter('{{%volumes}}', 'url', 'string', 'type');
		}

		if (!$this->db->columnExists('{{%assetindexdata}}', 'timestamp'))
		{
			$this->addColumnAfter('{{%assetindexdata}}', 'timestamp', 'datetime', 'size');
		}

		if ($this->db->columnExists('{{%assets}}', 'sourceId'))
		{
			MigrationHelper::renameColumn('{{%assets}}', 'sourceId', 'volumeId', $this);
		}

		if ($this->db->columnExists('{{%assetfolders}}', 'sourceId'))
		{
			MigrationHelper::renameColumn('{{%assetfolders}}', 'sourceId', 'volumeId', $this);
		}

		if ($this->db->columnExists('{{%assetindexdata}}', 'sourceId'))
		{
			MigrationHelper::renameColumn('{{%assetindexdata}}', 'sourceId', 'volumeId', $this);
		}

		if ($this->db->columnExists('{{%assettransformindex}}', 'sourceId'))
		{
			MigrationHelper::renameColumn('{{%assettransformindex}}', 'sourceId', 'volumeId', $this);
		}

		// Update permissions
		$permissions = (new Query())
			->select('id, name')
			->from('{{%userpermissions}}')
			->where(['like', 'name', '%assetsource%', false])
			->all();

		foreach ($permissions as $permission)
		{
			$newName = str_replace('assetsource', 'volume', $permission['name']);
			$this->update('{{%userpermissions}}', ['name' => $newName], ['id' => $permission['id']], [], false);
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
