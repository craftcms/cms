<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m160919_000000_usergroup_handle_title_unique extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$this->_handleDupes('handle');
		$this->_handleDupes('name');


		Craft::log('Adding unique index to handle column in the usergroups table.', LogLevel::Info, true);
		$this->createIndex('usergroups', 'handle', true);

		Craft::log('Adding unique index to name column in the usergroups table.', LogLevel::Info, true);
		$this->createIndex('usergroups', 'name', true);

		return true;
	}

	/**
	 * @param string $type Either 'handle' or 'name'
	 */
	private function _handleDupes($type)
	{
		// Get any duplicates.
		$duplicates = craft()->db->createCommand()
			->select($type)
			->from('usergroups')
			->group($type)
			->having('count("'.$type.'") > 1')
			->queryAll();

		if ($duplicates)
		{
			Craft::log('Found '.count($duplicates).' duplicate '.$type.'s.', LogLevel::Info, true);

			foreach ($duplicates as $duplicate)
			{
				Craft::log('Duplidate: '.$duplicate[$type], LogLevel::Info, true);

				$rows = craft()->db->createCommand()
					->select('*')
					->from('usergroups')
					->where($type.'=:type', array('type' => $duplicate[$type]))
					->order('dateCreated')
					->queryAll();

				// Find anything?
				if ($rows)
				{
					// Skip the first (the earliest created), since presumably it's the good one.
					unset($rows[0]);

					if ($rows)
					{
						foreach ($rows as $row)
						{
							// Let's give this 100 tries.
							for ($counter = 1; $counter <= 100; $counter++)
							{
								if ($type == 'handle')
								{
									$newString = $duplicate[$type].$counter;
								}
								else
								{
									$newString = $duplicate[$type].' '.$counter;
								}

								$exists = craft()->db->createCommand()
									->select('*')
									->from('usergroups')
									->where($type.'=:type', array('type' => $newString))
									->queryAll();

								// Found a free one.
								if (!$exists)
								{
									break;
								}
							}

							Craft::log('Updating user group '.$type.' from '.$row[$type].' to '.$newString, LogLevel::Info, true);

							// Let's update with a unique one.
							craft()->db->createCommand()->update(
								'usergroups',
								array($type => $newString),
								array('id' => $row['id'])
							);
						}
					}
				}
				else
				{
					Craft::log('Did not find any duplicate user group '.$type.'s.', LogLevel::Info, true);
				}
			}
		}
	}
}
