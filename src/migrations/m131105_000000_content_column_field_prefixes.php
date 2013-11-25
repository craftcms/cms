<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m131105_000000_content_column_field_prefixes extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$contentTable = $this->dbConnection->schema->getTable('{{content}}');

		if ($contentTable)
		{
			$fieldHandles = craft()->db->createCommand()->select('handle')->from('fields')->queryColumn();
			$newFieldHandles = array();
			$newColumnNames = array();

			foreach ($fieldHandles as $handleIndex => $handle)
			{
				// Is it going to be too long to get the field_ prefix?
				if (strlen($handle) > 58)
				{
					// Cut it down to size
					$newHandle = substr($handle, 0, 58);

					// Is that name already taken?
					if (in_array($newHandle, $fieldHandles))
					{
						$foundUnique = false;

						for ($i = 1; $i <= 9; $i++)
						{
							$testHandle = substr($newHandle, 0, 57).$i;

							if (!in_array($testHandle, $fieldHandles))
							{
								$newHandle = $testHandle;
								$foundUnique = true;
								break;
							}
						}

						if (!$foundUnique)
						{
							Craft::log('Could not find a unique columnn name for the "'.$handle.'" field.', LogLevel::Error);
							return false;
						}
					}

					$fieldHandles[$handleIndex] = $newHandle;
					$newFieldHandles[$handle] = $newHandle;

					// Does this field have a content column?
					if ($contentTable->getColumn($handle))
					{
						$newColumnNames[$handle] = 'field_'.$newHandle;
					}
				}
				else
				{
					// Does this field have a content column?
					if ($contentTable->getColumn($handle))
					{
						$newColumnNames[$handle] = 'field_'.$handle;
					}
				}
			}

			foreach ($newFieldHandles as $oldHandle => $newHandle)
			{
				$this->update('fields', array(
					'handle' => $newHandle
				), array(
					'handle' => $oldHandle
				));
			}

			foreach ($newColumnNames as $oldName => $newName)
			{
				$this->renameColumn('content', $oldName, $newName);
			}

			return true;
		}
		else
		{
			Craft::log('Could not find the `content` table. Wut?', LogLevel::Error);
			return false;
		}
	}
}
