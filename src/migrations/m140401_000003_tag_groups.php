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

		if (craft()->db->tableExists('tagsets'))
		{
			Craft::log('Renaming the tagsets table to taggroups.', LogLevel::Info, true);
			MigrationHelper::renameTable('tagsets', 'taggroups');
		}

		if (craft()->db->columnExists('tags', 'setId'))
		{
			Craft::log('Renaming the tags.setId column to groupId.', LogLevel::Info, true);
			MigrationHelper::renameColumn('tags', 'setId', 'groupId');
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
