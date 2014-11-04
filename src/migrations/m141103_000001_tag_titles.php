<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m141103_000001_tag_titles extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		Craft::log('Making tag titles translatable...', LogLevel::Info, true);

		// Select all of the tag names
		$tags = craft()->db->createCommand()
			->select('id, name')
			->from('tags')
			->queryAll();

		foreach ($tags as $tag)
		{
			$this->update('content', array(
				'title' => $tag['name']
			), array(
				'elementId' => $tag['id']
			));
		}

		$this->createIndex('tags', 'groupId');
		MigrationHelper::dropIndexIfExists('tags', array('name', 'groupId'), true);
		MigrationHelper::dropIndexIfExists('tags', array('groupId', 'name'), true);
		$this->dropColumn('tags', 'name');

		Craft::log('Done making tag titles translatable.', LogLevel::Info, true);

		return true;
	}
}
