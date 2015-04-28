<?php

namespace craft\app\migrations;

use Craft;
use craft\app\db\Migration;
use craft\app\db\Query;

/**
 * m150428_231346_userpreferences migration.
 */
class m150428_231346_userpreferences extends Migration
{
	// Properties
	// =========================================================================

	private $_usersTable;
	private $_prefsTable;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function safeUp()
	{
		$this->_usersTable = $this->db->getSchema()->getRawTableName('{{%users}}');
		$this->_prefsTable = $this->db->getSchema()->getRawTableName('{{%userpreferences}}');

		if ($this->db->tableExists($this->_prefsTable))
		{
			$this->truncateTable($this->_prefsTable);
			$this->dropForeignKey($this->db->getForeignKeyName($this->_prefsTable, 'userId'), $this->_prefsTable);
			$this->_createUserPrefsIndexAndForeignKey();
			return;
		}

		$this->_createUserPrefsTable();
		$this->_createUserPrefsIndexAndForeignKey();
		$this->_populateUserPrefsTable();

		$this->dropForeignKey($this->db->getForeignKeyName($this->_usersTable, 'preferredLocale'), $this->_usersTable);
		$this->dropColumn($this->_usersTable, 'preferredLocale');
		$this->dropColumn($this->_usersTable, 'weekStartDay');
	}

	/**
	 * @inheritdoc
	 */
	public function safeDown()
	{
		echo "m150428_231346_userpreferences cannot be reverted.\n";
		return false;
	}

	// Private Methods
	// =========================================================================

	/**
	 * Creates the userpreferences table
	 */
	private function _createUserPrefsTable()
	{
		$this->createTable(
			$this->_prefsTable,
			[
				'userId' => 'integer(11) NOT NULL DEFAULT \'0\'',
				'preferences' => 'text COLLATE utf8_unicode_ci',
			],
			null,
			false,
			false
		);
	}

	/**
	 * Creates an index and foreign key on the `userId` column on the userpreferences table.
	 */
	private function _createUserPrefsIndexAndForeignKey()
	{
		$this->createIndex(
			$this->db->getIndexName($this->_prefsTable, 'userId', true),
			$this->_prefsTable,
			'userId',
			true
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName($this->_prefsTable, 'userId'),
			$this->_prefsTable,
			'userId',
			$this->_usersTable,
			'id',
			'CASCADE'
		);
	}

	/**
	 * Populates the userpreferences table.
	 */
	private function _populateUserPrefsTable()
	{
		$users = (new Query())
			->select(['id', 'preferredLocale', 'weekStartDay'])
			->from($this->_usersTable)
			->where(['or', 'preferredLocale is not null', 'weekStartDay != \'0\''])
			->all($this->db);

		if (!empty($users))
		{
			$rows = [];

			foreach ($users as $user)
			{
				$prefs = [];

				if (!empty($user['preferredLocale']))
				{
					$prefs['locale'] = $user['preferredLocale'];
				}

				if ($user['weekStartDay'] != 0)
				{
					$prefs['weekStartDay'] = $user['weekStartDay'];
				}

				$rows[] = [$user['id'], JsonHelper::encode($prefs)];
			}

			$this->batchInsert($this->_prefsTable, ['userId', 'preferences'], $rows, false);
		}
	}
}
