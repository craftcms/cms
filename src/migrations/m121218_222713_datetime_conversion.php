<?php
namespace Blocks;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m121218_222713_datetime_conversion extends \CDbMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		ini_set('memory_limit', '64M');

		// Find all record classes
		$records = blx()->install->findInstallableRecords();

		// Add any section content records to the mix
		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			$criteria = new SectionCriteria();
			$criteria->limit = null;
			$sections = blx()->sections->findSections($criteria);

			foreach ($sections as $section)
			{
				$records[] = new SectionContentRecord($section);
			}
		}

		// Grab any plugin records
		$plugins = blx()->plugins->getPlugins(false);

		foreach ($plugins as $plugin)
		{
			if ($plugin->isInstalled)
			{
				$pluginRecords = $plugin->getRecords();

				if (!empty($pluginRecords))
				{
					$records = array_merge($records, $pluginRecords);
				}
			}
		}

		// Flatten the records out
		$flattenedRecords = array();
		foreach ($records as $record)
		{
			$flattenedRecords[$record->getTableName()] = $record->defineAttributes();
		}

		if (Blocks::hasPackage(BlocksPackage::Users))
		{
			// Manually add usergroups_users
			$flattenedRecords['usergroups_users'] = array();
		}

		// Manually add migrations table if it exists
		if (blx()->db->schema->getTable(blx()->config->getDbItem('tablePrefix').'_'.blx()->migrations->migrationTable) !== null)
		{
			$flattenedRecords['migrations'] = array(
				'apply_time' => array(
					0 => ColumnType::DateTime,
					'required' => true,
				)
			);
		}

		// Add the activity table
		$flattenedRecords['activity'] = array(
			'logtime' => array(
				0 => ColumnType::DateTime,
				'required' => true,
			)
		);

		$dbTimeZone = $this->_getDbTimeZone();

		foreach ($flattenedRecords as $tableName => $attributes)
		{
			// Manually add dateCreated and dateUpdated
			$attributes['dateUpdated'] = array(0 => ColumnType::DateTime, 'required' => true);
			$attributes['dateCreated'] = array(0 => ColumnType::DateTime, 'required' => true);

			foreach ($attributes as $column => $attribute)
			{
				$config = ModelHelper::normalizeAttributeConfig($attribute);

				// We found a "DateTime" column.
				if (isset($config['type']) && $config['type'] == AttributeType::DateTime)
				{
					$tempColumn = $column.'_'.StringHelper::randomString(12);

					$required = false;
					if (isset($config['required']) && $config['required'] == true)
					{
						$required = true;
					}

					// Add a temp column immediately after the one we found.
					blx()->db->createCommand()->addColumnAfter($tableName, $tempColumn, array('column' => ColumnType::DateTime, 'null' => !$required), $column);

					$fullTableName = blx()->config->getDbItem('tablePrefix').'_'.$tableName;

					// We know the DB's timezone, so let's do the slower, more accurate, row X row conversion.
					if ($dbTimeZone)
					{
						$rows = blx()->db->createCommand('SELECT * FROM `'.$fullTableName.'`')->queryAll();

						foreach ($rows as $row)
						{
							$oldTime = $row[$column];

							// No need to convert if the current value is empty.
							if (!empty($oldTime))
							{
								if (DateTimeHelper::isValidTimeStamp($oldTime))
								{
									$convertedTime = blx()->db->createCommand("SELECT CONVERT_TZ(FROM_UNIXTIME({$oldTime}), '{$dbTimeZone}', '+00:00') AS timezone;")->queryRow();
									$convertedTime = $convertedTime['timezone'];

									blx()->db->createCommand("UPDATE `{$fullTableName}` SET `{$tempColumn}` = '{$convertedTime}' WHERE `id` = {$row['id']}")->execute();
								}
							}
						}
					}
					// Faster, but since we don't know the DB's timezone, there may be timestamp conversion loss since
					// FROM_UNIXTIMESTAMP uses the timezone MySQL is set to.
					else
					{
						blx()->db->createCommand("UPDATE `{$fullTableName}` SET `{$tempColumn}`=FROM_UNIXTIME({$column})")->execute();
					}

					// Drop the original column
					blx()->db->createCommand()->dropColumn($tableName, $column);

					// Rename the temp column
					blx()->db->createCommand()->renameColumn($tableName, $tempColumn, $column);
				}
			}
		}

		// Refresh the schema metadata so the rest of the request sees the schema changes.
		blx()->db->getSchema()->refresh();
		InfoRecord::model()->refreshMetaData();

		return true;
	}

	/**
	 * @return bool|string
	 */
	private function _getDbTimeZone()
	{
		$dbTimeZone = blx()->db->createCommand("SELECT TIMEDIFF(NOW(), CONVERT_TZ(NOW(), @@GLOBAL.time_zone, '+00:00')) AS timezone;")->queryRow();

		if (!empty($dbTimeZone))
		{
			$dbTimeZone = $dbTimeZone['timezone'];

			// In case MySQL returns in "-08:00:00" format.
			if (preg_match('/^(\+|-)[0-9]{2}:[0-9]{2}:[0-9]{2}$/', $dbTimeZone))
			{
				$dbTimeZone = substr($dbTimeZone, 0, strlen($dbTimeZone) -3);
			}

			// Make sure MySQL returns timezone in a format we're expecting.
			if (preg_match('/^(\+|-)[0-9]{2}:[0-9]{2}$/', $dbTimeZone))
			{
				return $dbTimeZone;
			}
		}

		Blocks::log('Unable to determine the timezone MySQL is using.  Date/Time conversion loss may occur.', \CLogger::LEVEL_WARNING);
		return false;
	}
}
