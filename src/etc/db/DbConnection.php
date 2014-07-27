<?php
namespace Craft;

/**
 * Class DbConnection
 *
 * @package craft.app.etc.db
 */
class DbConnection extends \CDbConnection
{
	/**
	 *
	 */
	public function init()
	{
		try
		{
			$this->connectionString = $this->_processConnectionString();
			$this->emulatePrepare   = true;
			$this->username         = craft()->config->get('user', ConfigFile::Db);
			$this->password         = craft()->config->get('password', ConfigFile::Db);
			$this->charset          = craft()->config->get('charset', ConfigFile::Db);
			$this->tablePrefix      = $this->getNormalizedTablePrefix();

			parent::init();
		}
		// Most likely missing PDO in general or the specific database PDO driver.
		catch(\CDbException $e)
		{
			Craft::log($e->getMessage(), LogLevel::Error);
			$missingPdo = false;

			// TODO: Multi-db driver check.
			if (!extension_loaded('pdo'))
			{
				$missingPdo = true;
				$messages[] = Craft::t('Craft requires the PDO extension to operate.');
			}

			if (!extension_loaded('pdo_mysql'))
			{
				$missingPdo = true;
				$messages[] = Craft::t('Craft requires the PDO_MYSQL driver to operate.');
			}

			if (!$missingPdo)
			{
				Craft::log($e->getMessage(), LogLevel::Error);
				$messages[] = Craft::t('Craft can’t connect to the database with the credentials in craft/config/db.php.');
			}
		}
		catch (\Exception $e)
		{
			Craft::log($e->getMessage(), LogLevel::Error);
			$messages[] = Craft::t('Craft can’t connect to the database with the credentials in craft/config/db.php.');
		}

		if (!empty($messages))
		{
			throw new DbConnectException(Craft::t('{errors}', array('errors' => implode('<br />', $messages))));
		}

		craft()->setIsDbConnectionValid(true);

		// Now that we've validated the config and connection, set extra db logging if devMode is enabled.
		if (craft()->config->get('devMode'))
		{
			$this->enableProfiling = true;
			$this->enableParamLogging = true;
		}
	}

	/**
	 * @param null $query
	 * @return DbCommand
	 */
	public function createCommand($query = null)
	{
		$this->setActive(true);
		return new DbCommand($this, $query);
	}

	/**
	 * @return bool|string
	 */
	public function backup()
	{
		$backup = new DbBackup();
		if (($backupFile = $backup->run()) !== false)
		{
			return $backupFile;
		}

		return false;
	}

	/**
	 * @param $name
	 * @return string
	 */
	public function quoteDatabaseName($name)
	{
		return $this->getSchema()->quoteTableName($name);
	}

	/**
	 * Returns whether a table exists.
	 *
	 * @param string $table
	 * @param bool $refresh
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
	 * @param string $table
	 * @param string $column
	 * @param bool $refresh
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
	 * @return bool
	 */
	public function isDbConnectionValid()
	{
		return $this->_isDbConnectionValid;
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
	 * @param string|aray $table The table name or an array of table names
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
	 * @param string $table
	 * @param string|array $columns
	 * @return string
	 */
	public function getForeignKeyName($table, $columns)
	{
		$columns = ArrayHelper::stringToArray($columns);
		$name = $this->tablePrefix.$table.'_'.implode('_', $columns).'_fk';
		return $this->trimObjectName($name);
	}

	/**
	 * Returns an index name based on the table, column names, and whether it should be unique.
	 *
	 * @param string $table
	 * @param string|array $columns
	 * @param bool $unique
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
	 * @param string $table
	 * @param string|array $columns
	 * @param bool $unique
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

	/**
	 * Returns the correct connection string depending on whether a unixSocket is specific or not in the db config.
	 *
	 * @return string
	 */
	private function _processConnectionString()
	{
		$unixSocket = craft()->config->get('unixSocket', ConfigFile::Db);
		if (!empty($unixSocket))
		{
			return strtolower('mysql:unix_socket='.$unixSocket.';dbname=').craft()->config->get('database', ConfigFile::Db).';';
		}
		else
		{
			return strtolower('mysql:host='.craft()->config->get('server', ConfigFile::Db).';dbname=').craft()->config->get('database', ConfigFile::Db).strtolower(';port='.craft()->config->get('port', ConfigFile::Db).';');
		}
	}
}
