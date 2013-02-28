<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130305_000000_unique_section_names extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// Find any duplicate section names
		$dupes = craft()->db->createCommand()
			->select('id,name')
			->from('sections')
			->group('name')
			->having('count(*) > 1')
			->queryAll();

		foreach ($dupes as $dupe)
		{
			// Get the other sections with this name
			$otherDupes = craft()->db->createCommand()
				->select('id')
				->from('sections')
				->where('name = :name AND id != :id', array(':name' => $dupe['name'], ':id' => $dupe['id']))
				->queryAll();

			$i = 0;

			foreach ($otherDupes as $otherDupe)
			{
				// Find this section a unique name
				do
				{
					$i++;
					$name = $dupe['name'].' '.$i;

					$totalSections = (int) craft()->db->createCommand()
						->select('count(id)')
						->from('sections')
						->where('name = :name', array(':name' => $name))
						->queryScalar();
				}
				while ($totalSections);

				// Update this section's name
				$this->update('sections',
					array('name' => $name),
					array('id' => $otherDupe['id'])
				);
			}
		}

		// Now that we've taken care of all the dupes, add a unique index
		$this->createIndex('sections', 'name', true);

		return true;
	}
}
