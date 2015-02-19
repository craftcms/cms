<?php
namespace Craft;

/**
 * Class DbConnection
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.etc.db
 * @since     1.0
 */
class DbConnection extends \CDbConnection
{
	// Public Methods
	// =========================================================================

	/**
	 * @param null $query
	 *
	 * @return DbCommand
	 */
	public function createCommand($query = null)
	{
		$this->setActive(true);

		return new DbCommand($this, $query);
	}

	/**
	 * Performs a database backup.
	 *
	 * @param array|null $ignoreDataTables If set to an empty array, a full database backup will be performed. If set
	 *                                     to an array or database table names, they will get merged with the default
	 *                                     list of table names whose data is to be ignored during a database backup.
	 *
	 * @return bool|string The file path to the database backup, or false if something went wrong.
	 */
	public function backup($ignoreDataTables = null)
	{
		$backup = new DbBackup();

		if ($ignoreDataTables !== null)
		{
			$backup->setIgnoreDataTables($ignoreDataTables);
		}

		if (($backupFile = $backup->run()) !== false)
		{
			// Fire an 'onBackup' event
			$this->onBackup(new Event($this, array(
				'filePath' => $backupFile,
			)));

			return $backupFile;
		}

		return false;
	}

	/**
	 * @param $name
	 *
	 * @return string
	 */
	public function quoteDatabaseName($name)
	{
		return $this->getSchema()->quoteTableName($name);
	}

	/**
	 * Returns whether a table exists.
	 *
	 * @param string    $table
	 * @param bool|null $refresh
	 *
	 * @return bool
	 */
	public function tableExists($table, $refresh = null)
	{
		// Default to refreshing the tables if Craft isn't installed yet
		if ($refresh || ($refresh === null && !craft()->isInstalled()))
		{
			$this->getSchema()->refresh();
		}

		$table = $this->addTablePrefix($table);

		return in_array($table, $this->getSchema()->getTableNames());
	}

	/**
	 * Checks if a column exists in a table.
	 *
	 * @param string    $table
	 * @param string    $column
	 * @param bool|null $refresh
	 *
	 * @return bool
	 */
	public function columnExists($table, $column, $refresh = null)
	{
		// Default to refreshing the tables if Craft isn't installed yet
		if ($refresh || ($refresh === null && !craft()->isInstalled()))
		{
			$this->getSchema()->refresh();
		}

		$table = $this->getSchema()->getTable('{{'.$table.'}}');

		if ($table)
		{
			if (($column = $table->getColumn($column)) !== null)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @return string
	 */
	public function getNormalizedTablePrefix()
	{
		// Table prefixes cannot be longer than 5 characters
		$tablePrefix = rtrim(craft()->config->get('tablePrefix', ConfigFile::Db), '_');

		if ($tablePrefix)
		{
			if (strlen($tablePrefix) > 5)
			{
				$tablePrefix = substr($tablePrefix, 0, 5);
			}

			$tablePrefix .= '_';
		}
		else
		{
			$tablePrefix = '';
		}

		return $tablePrefix;

	}

	/**
	 * Adds the table prefix to the passed-in table name(s).
	 *
	 * @param string|array $table The table name or an array of table names
	 *
	 * @return string|array The modified table name(s)
	 */
	public function addTablePrefix($table)
	{
		if (is_array($table))
		{
			foreach ($table as $key => $t)
			{
				$table[$key] = $this->addTablePrefix($t);
			}
		}
		else
		{
			$table = preg_replace('/^\w+/', $this->tablePrefix.'\0', $table);
		}

		return $table;
	}

	/**
	 * Returns a foreign key name based on the table and column names.
	 *
	 * @param string       $table
	 * @param string|array $columns
	 *
	 * @return string
	 */
	public function getForeignKeyName($table, $columns)
	{
		$columns = ArrayHelper::stringToArray($columns);
		$name = $this->tablePrefix.$table.'_'.implode('_', $columns).'_fk';

		return $this->trimObjectName($name);
	}

	/**
	 * Returns an index name based on the table, column names, and whether
	 * it should be unique.
	 *
	 * @param string       $table
	 * @param string|array $columns
	 * @param bool         $unique
	 *
	 * @return string
	 */
	public function getIndexName($table, $columns, $unique = false)
	{
		$columns = ArrayHelper::stringToArray($columns);
		$name = $this->tablePrefix.$table.'_'.implode('_', $columns).($unique ? '_unq' : '').'_idx';

		return $this->trimObjectName($name);
	}

	/**
	 * Returns a primary key name based on the table and column names.
	 *
	 * @param string       $table
	 * @param string|array $columns
	 *
	 * @return string
	 */
	public function getPrimaryKeyName($table, $columns)
	{
		$columns = ArrayHelper::stringToArray($columns);
		$name = $this->tablePrefix.$table.'_'.implode('_', $columns).'_pk';

		return $this->trimObjectName($name);
	}

	/**
	 * Ensures that an object name is within the schema's limit.
	 *
	 * @param string $name
	 * @return string
	 */
	public function trimObjectName($name)
	{
		$schema = $this->getSchema();

		// TODO: Remember to set this on any other supported databases in the future
		if (!isset($schema->maxObjectNameLength))
		{
			return $name;
		}

		$name = trim($name, '_');
		$nameLength = mb_strlen($name);

		if ($nameLength > $schema->maxObjectNameLength)
		{
			$parts = array_filter(explode('_', $name));
			$totalParts = count($parts);
			$totalLetters = $nameLength - ($totalParts-1);
			$maxLetters = $schema->maxObjectNameLength - ($totalParts-1);

			// Consecutive underscores could have put this name over the top
			if ($totalLetters > $maxLetters)
			{
				foreach ($parts as $i => $part)
				{
					$newLength = round($maxLetters * mb_strlen($part) / $totalLetters);
					$parts[$i] = mb_substr($part, 0, $newLength);
				}
			}

			$name = implode('_', $parts);

			// Just to be safe
			if (mb_strlen($name) > $schema->maxObjectNameLength)
			{
				$name = mb_substr($name, 0, $schema->maxObjectNameLength);
			}
		}

		return $name;
	}

	// Events
	// -------------------------------------------------------------------------

	/**
	 * Fires an 'onBackup' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onBackup(Event $event)
	{
		$this->raiseEvent('onBackup', $event);
	}
}
