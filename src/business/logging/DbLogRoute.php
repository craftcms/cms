<?php
namespace Blocks;

/**
 *
 */
class DbLogRoute extends \CDbLogRoute
{
	public function init()
	{
		$this->autoCreateLogTable = true;
		$this->levels = 'activity';
		$this->connectionID = 'db';
		$this->logTableName = b()->config->getTablePrefix().'activity';

		parent::init();
	}

	/**
	 * Creates the DB table for storing log messages.
	 * @param \CDbConnection $db the database connection
	 * @param string $tableName the name of the table to be created
	 */
	protected function createLogTable($db, $tableName)
	{
		$driver = $db->getDriverName();

		if($driver === 'mysql')
			$logID = '`id` INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY';
		else if ($driver === 'pgsql')
			$logID = '`id` SERIAL PRIMARY KEY';
		else
			$logID = '`id` INTEGER NOT NULL PRIMARY KEY';

		$sql = "CREATE TABLE `{$tableName}`
				 ({$logID},
					`category` VARCHAR(128),
					`message` TEXT,
					`logtime` INTEGER,
					INDEX `category_idx` (`category`),
					INDEX `logtime_idx` (`logtime`));";
		$db->createCommand($sql)->execute();
	}

	/**
	 * Stores log messages into database.
	 * @param array $logs list of log messages
	 */
	protected function processLogs($logs)
	{
		$sql="
			INSERT INTO {$this->logTableName}
			(category, message, logtime) VALUES
			(:category, :message, :logtime)
		";

		$command = $this->getDbConnection()->createCommand($sql);

		foreach($logs as $log)
		{
			$command->bindValue(':category',$log[2]);
			$command->bindValue(':message',$log[0]);
			$command->bindValue(':logtime',(int)$log[3]);
			$command->execute();
		}
	}
}
