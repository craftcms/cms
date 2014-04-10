<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140401_000003_tag_groups extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		MigrationHelper::refresh();
		$addFkBack = false;

		if (craft()->db->tableExists('tagsets'))
		{
			// A couple people have had failed updates that resulted in tagsets *and* taggroups tables lying around
			// causing a MySQL error if trying to rename the tagsets table
			// so let's make sure it's gone first.
			if (craft()->db->tableExists('taggroups'))
			{
				MigrationHelper::dropForeignKeyIfExists('taggroups', array('fieldLayoutId'));

				if (craft()->db->columnExists('tags', 'groupId'))
				{
					MigrationHelper::dropForeignKeyIfExists('tags', array('groupId'));
					MigrationHelper::renameColumn('tags', 'groupId', 'setId');
					$addFkBack = true;
				}

				$this->dropTable('taggroups');

				// ...and refresh the schema cache
				craft()->db->getSchema()->refresh();
			}

			Craft::log('Renaming the tagsets table to taggroups.', LogLevel::Info, true);
			MigrationHelper::renameTable('tagsets', 'taggroups');
		}

		if (craft()->db->columnExists('tags', 'setId'))
		{
			Craft::log('Renaming the tags.setId column to groupId.', LogLevel::Info, true);
			MigrationHelper::renameColumn('tags', 'setId', 'groupId');
		}

		if ($addFkBack)
		{
			$this->addForeignKey('tags', 'groupId', 'taggroups', 'id', null, 'CASCADE');
		}

		Craft::log('Updating the Tags fields\' settings.', LogLevel::Info, true);

		$fields = craft()->db->createCommand()
			->select('id, settings')
			->from('fields')
			->where('type="Tags"')
			->queryAll();

		foreach ($fields as $field)
		{
			$settings = JsonHelper::decode($field['settings']);

			if (isset($settings['source']) && strncmp($settings['source'], 'tagset:', 7) === 0)
			{
				$settings['source'] = 'taggroup:'.substr($settings['source'], 7);

				$this->update('fields', array(
					'settings' => JsonHelper::encode($settings)
				), array(
					'id' => $field['id']
				));
			}
		}

		return true;
	}
}
