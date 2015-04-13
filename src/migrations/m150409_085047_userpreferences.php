<?php

namespace craft\app\migrations;

use Craft;
use craft\app\db\Migration;
use craft\app\db\Query;
use craft\app\helpers\JsonHelper;
use craft\app\helpers\MigrationHelper;
use yii\db\Schema;

/**
 * m150409_085047_userpreferences migration.
 */
class m150409_085047_userpreferences extends Migration
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function safeUp()
	{
		$usersTable = $this->db->getSchema()->getRawTableName('{{%users}}');
		$prefsTable = $this->db->getSchema()->getRawTableName('{{%userpreferences}}');

		$this->createTable(
			$prefsTable,
			[
				'userId' => 'pk',
				'preferences' => 'text COLLATE utf8_unicode_ci',
			],
			null,
			false,
			false
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName($prefsTable, 'userId'),
			$prefsTable,
			'userId',
			$usersTable,
			'id',
			'CASCADE'
		);

		$users = (new Query())
			->select(['id', 'preferredLocale', 'weekStartDay'])
			->from($usersTable)
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

			$this->batchInsert($prefsTable, ['userId', 'preferences'], $rows, false);
		}

		$this->dropForeignKey($this->db->getForeignKeyName($usersTable, 'preferredLocale'), $usersTable);
		$this->dropColumn($usersTable, 'preferredLocale');
		$this->dropColumn($usersTable, 'weekStartDay');
	}

	/**
	 * @inheritdoc
	 */
	public function safeDown()
	{
		echo "m150409_085047_userpreferences cannot be reverted.\n";
		return false;
	}
}
