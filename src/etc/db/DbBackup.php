<?php
namespace Craft;

/**
 * This class provides methods for backing up and restore Craft databases.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.etc.db
 * @since     1.0
 */
class DbBackup
{
	// Properties
	// =========================================================================

	/**
	 * Holds the list of foreign key constraints for the database.
	 *
	 * @var array
	 */
	private $_constraints;

	/**
	 * Stores the current Craft version/build of the database that is being backed up.
	 *
	 * @var string
	 */
	private $_currentVersion;

	/**
	 * The file path to the database backup.
	 *
	 * @var string
	 */
	private $_filePath;

	/**
	 * A list of tables that will be ignored during the database backup. These store temporary data, that is O.K. to
	 * lose and will be re-created as needed.
	 *
	 * @var array
	 */
	private $_ignoreDataTables = array('assetindexdata', 'templatecaches', 'templatecachecriteria', 'templatecacheelements');

	// Public Methods
	// =========================================================================

	/**
	 * Controls which (if any) tables will have their data ignored in this database backup.
	 *
	 * @param array|false $tables If set to an array, will merge the given tables with the default list of tables to
	 *                            ignore for data backup in $_ignoreDataTables.  If set to false, no tables will be
	 *                            ignored and a full database backup will be performed.
	 *
	 * @return null
	 */
	public function setIgnoreDataTables($tables)
	{
		if (is_array($tables))
		{
			$this->_ignoreDataTables = array_merge($this->_ignoreDataTables, $tables);
		}
		else if ($tables === false)
		{
			$this->_ignoreDataTables = array();
		}
	}

	/**
	 * Triggers the database backup including all DML and DDL and writes it out to a file.
	 *
	 * @return string The path to the database backup file.
	 */
	public function run()
	{
		// Normalize the ignored table names if there is a table prefix set.
		if (($tablePrefix = craft()->config->get('tablePrefix', ConfigFile::Db)) !== '')
		{
			foreach ($this->_ignoreDataTables as $key => $tableName)
			{
				$this->_ignoreDataTables[$key] = $tablePrefix.'_'.$tableName;
			}
		}

		$this->_currentVersion = 'v'.craft()->getVersion().'.'.craft()->getBuild();
		$fileName = IOHelper::cleanFilename(craft()->getSiteName()).'_'.gmdate('ymd_His').'_'.$this->_currentVersion.'.sql';
		$this->_filePath = craft()->path->getDbBackupPath().StringHelper::toLowerCase($fileName);

		$this->_processHeader();

		foreach (craft()->db->getSchema()->getTables() as $tableName => $val)
		{
			$this->_processTable($tableName);
		}

		$this->_processConstraints();
		$this->_processFooter();

		return $this->_filePath;
	}

	/**
	 * Restores a database backup with the given backup file. Note that all tables and data in the database will be
	 * deleted before the backup file is executed.
	 *
	 * @param $filePath The file path of the database backup to restore.
	 *
	 * @throws Exception
	 * @return null
	 */
	public function restore($filePath)
	{
		if (!IOHelper::fileExists($filePath))
		{
			throw new Exception(Craft::t('Could not find the SQL file to restore: {filePath}', array('filePath' => $filePath)));
		}

		$this->_nukeDb();

		$sql = IOHelper::getFileContents($filePath, true);

		array_walk($sql, array($this, 'trimValue'));
		$sql = array_filter($sql);

		$statements = $this->_buildSQLStatements($sql);

		foreach ($statements as $statement)
		{
			Craft::log('Executing SQL statement: '.$statement);
			$command = craft()->db->createCommand($statement);
			$command->execute();
		}
	}

	/**
	 * @param $value
	 *
	 * @return null
	 */
	public function trimValue(&$value)
	{
		$value = trim($value);
	}

	// Private Methods
	// =========================================================================

	/**
	 * @param array $sql
	 *
	 * @return array
	 */
	private function _buildSQLStatements($sql)
	{
		$statementArray = array();
		$runningStatement = '';

		foreach ($sql as $statement)
		{
			if ($statement[0] == '-')
			{
				continue;
			}

			if ($statement[mb_strlen($statement) - 1] == ';')
			{
				if (!$runningStatement)
				{
					$statementArray[] = $statement;
				}
				else
				{
					$statementArray[] = $runningStatement.$statement;
					$runningStatement = '';
				}

			}
			else
			{
				$runningStatement .= $statement;
			}
		}

		return $statementArray;
	}

	/**
	 * @return null
	 */
	private function _nukeDb()
	{
		Craft::log('Nuking DB');

		$databaseName = craft()->config->get('database', ConfigFile::Db);

		$sql = 'SET FOREIGN_KEY_CHECKS = 0;'.PHP_EOL.PHP_EOL;

		$tables = craft()->db->getSchema()->getTableNames();

		foreach ($tables as $table)
		{
			$sql .= 'DROP TABLE IF EXISTS '.craft()->db->quoteDatabaseName($databaseName).'.'.craft()->db->quoteTableName($table).';'.PHP_EOL;
		}

		$sql .= PHP_EOL.'SET FOREIGN_KEY_CHECKS = 1;'.PHP_EOL;

		$command = craft()->db->createCommand($sql);
		$command->execute();

		Craft::log('Database nuked.');
	}


	/**
	 * Generate the foreign key constraints for all tables.
	 *
	 * @return null
	 */
	private function _processConstraints()
	{
		$sql = '--'.PHP_EOL.'-- Constraints for tables'.PHP_EOL.'--'.PHP_EOL.PHP_EOL;
		$first = true;

		foreach ($this->_constraints as $tableName => $value)
		{
			if ($first && count($value[0]) > 0)
			{
				$sql .= PHP_EOL.'--'.PHP_EOL.'-- Constraints for table '.craft()->db->quoteTableName($tableName).PHP_EOL.'--'.PHP_EOL;
				$sql .= 'ALTER TABLE '.craft()->db->quoteTableName($tableName).PHP_EOL;
			}

			if (count($value[0]) > 0)
			{
				for ($i = 0; $i < count($value[0]); $i++)
				{
					if (mb_strpos($value[0][$i], 'CONSTRAINT') === false)
					{
						$sql .= preg_replace('/(FOREIGN[\s]+KEY)/', "\tADD $1", $value[0][$i]);
					}
					else
					{
						$sql .= preg_replace('/(CONSTRAINT)/', "\tADD $1", $value[0][$i]);
					}

					if ($i == count($value[0]) - 1)
					{
						$sql .= ";".PHP_EOL;
					}
					if ($i < count($value[0]) - 1)
					{
						$sql .=PHP_EOL;
					}
				}
			}
		}

		IOHelper::writeToFile($this->_filePath, $sql, true, true);
	}


	/**
	 * Set sql file header
	 *
	 * @return null
	 */
	private function _processHeader()
	{
		$header = '-- Generated by Craft '.$this->_currentVersion.' on '.DateTimeHelper::nice(DateTimeHelper::currentTimeStamp()).'.'.PHP_EOL.PHP_EOL;
		$header .= '--'.PHP_EOL.'-- Disable foreign key checks and autocommit.'.PHP_EOL.'--'.PHP_EOL.PHP_EOL;
		$header .= 'SET FOREIGN_KEY_CHECKS = 0;'.PHP_EOL;
		$header .= 'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";'.PHP_EOL;
		$header .= 'SET AUTOCOMMIT = 0;'.PHP_EOL;
		$header .= 'SET NAMES utf8;'.PHP_EOL.PHP_EOL;

		IOHelper::writeToFile($this->_filePath, $header, true, true);
	}


	/**j
	 * Set sql file footer
	 *
	 * @return null
	 */
	private function _processFooter()
	{
		IOHelper::writeToFile($this->_filePath, PHP_EOL.'SET FOREIGN_KEY_CHECKS = 1;'.PHP_EOL, true, true);
	}


	/**
	 * Create the SQL for a table dump
	 *
	 * @param $tableName
	 *
	 * @return null
	 */
	private function _processTable($tableName)
	{
		$db = craft()->db;

		$result = PHP_EOL.'--'.PHP_EOL.'-- Schema for table `'.$tableName.'`'.PHP_EOL.'--'.PHP_EOL;
		$result .= PHP_EOL.'DROP TABLE IF EXISTS '.$db->quoteTableName($tableName).';'.PHP_EOL.PHP_EOL;

		$q = $db->createCommand('SHOW CREATE TABLE '.$db->quoteTableName($tableName).';')->queryRow();
		$createQuery = $q['Create Table'];
		$pattern = '/CONSTRAINT.*|FOREIGN[\s]+KEY/';

		// constraints to $tableName
		preg_match_all($pattern, $createQuery, $this->_constraints[$tableName]);

		$createQuery = preg_split('/$\R?^/m', $createQuery);
		$createQuery = preg_replace($pattern, '', $createQuery);

		$removed = false;

		foreach ($createQuery as $key => $statement)
		{
			// Stupid PHP.
			$temp = trim($createQuery[$key]);

			if (empty($temp))
			{
				unset($createQuery[$key]);
				$removed = true;
			}
		}

		if ($removed)
		{
			$createQuery[count($createQuery) - 2] = rtrim($createQuery[count($createQuery) - 2], ',');
		}

		// resort the keys
		$createQuery = array_values($createQuery);

		for ($i = 0; $i < count($createQuery) - 1; $i++)
		{
				$result .= $createQuery[$i].PHP_EOL;
		}

		$result .= $createQuery[$i].';'.PHP_EOL;

		// Write out what we have so far.
		IOHelper::writeToFile($this->_filePath, $result, true, true);

		// See if we have any data.
		$totalRows =  $db->createCommand('SELECT count(*) FROM '.$db->quoteTableName($tableName).';')->queryScalar();

		if ($totalRows == 0)
		{
			return;
		}

		if (!in_array($tableName, $this->_ignoreDataTables))
		{
			// Data!
			IOHelper::writeToFile($this->_filePath, PHP_EOL.'--'.PHP_EOL.'-- Data for table `'.$tableName.'`'.PHP_EOL.'--'.PHP_EOL.PHP_EOL, true, true);

			$batchSize = 1000;

			// Going to grab the data in batches.
			$totalBatches = ceil($totalRows / $batchSize);

			for ($counter = 0; $counter < $totalBatches; $counter++)
			{
				@set_time_limit(120);

				$offset = $batchSize * $counter;
				$rows =  $db->createCommand('SELECT * FROM '.$db->quoteTableName($tableName).' LIMIT '.$offset.','.$batchSize.';')->queryAll();

				if (!empty($rows))
				{
					$attrs = array_map(array($db, 'quoteColumnName'), array_keys($rows[0]));

					foreach($rows as $row)
					{
						$insertStatement = 'INSERT INTO '.$db->quoteTableName($tableName).' ('.implode(', ', $attrs).') VALUES';

						// Process row
						foreach($row as $columnName => $value)
						{
							if ($value === null)
							{
								$row[$columnName] = 'NULL';
							}
							else
							{
								$row[$columnName] = $db->getPdoInstance()->quote($value);
							}
						}

						$insertStatement .= ' ('.implode(', ', $row).');';
						IOHelper::writeToFile($this->_filePath, $insertStatement.PHP_EOL, true, true);
					}
				}
			}

			IOHelper::writeToFile($this->_filePath, PHP_EOL.PHP_EOL, true, true);
		}
	}
}
