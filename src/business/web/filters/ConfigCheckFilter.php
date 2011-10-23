<?php

class ConfigCheckFilter extends CFilter
{
	protected function preFilter($filterChain)
	{
		if(Blocks::app()->controller->id == 'site' && Blocks::app()->controller->action->id == 'error')
			return true;

		if(Blocks::app()->controller->getModule() !== null)
			if(Blocks::app()->controller->getModule()->id == 'install' && Blocks::app()->controller->id == 'default')
				return true;

		$messages = array();

		$databaseServerName = Blocks::app()->configRepo->getDatabaseServerName();
		$databaseAuthName = Blocks::app()->configRepo->getDatabaseAuthName();
		$databaseAuthPassword = Blocks::app()->configRepo->getDatabaseAuthPassword();
		$databaseName = Blocks::app()->configRepo->getDatabaseName();
		$databaseType = Blocks::app()->configRepo->getDatabaseType();
		$databasePort = Blocks::app()->configRepo->getDatabasePort();
		$databaseTablePrefix = Blocks::app()->configRepo->getDatabaseTablePrefix();
		$databaseCharset = Blocks::app()->configRepo->getDatabaseCharset();
		$databaseCollation = Blocks::app()->configRepo->getDatabaseCollation();

		if (StringHelper::IsNullOrEmpty($databaseServerName))
			$messages[] = 'The database server name is not set in your db config file.';

		if (StringHelper::IsNullOrEmpty($databaseAuthName))
			$messages[] = 'The database user name is not set in your db config file.';

		if (StringHelper::IsNullOrEmpty($databaseAuthPassword))
			$messages[] = 'The database password is not set in your db config file.';

		if (StringHelper::IsNullOrEmpty($databaseName))
			$messages[] = 'The database name is not set in your db config file.';

		if (StringHelper::IsNullOrEmpty($databasePort))
			$messages[] = 'The database port is not set in your db config file.';

		if (StringHelper::IsNullOrEmpty($databaseTablePrefix))
			$messages[] = 'The database table prefix is not set in your db config file.';

		if (StringHelper::IsNullOrEmpty($databaseCharset))
			$messages[] = 'The database charset is not set in your db config file.';

		if (StringHelper::IsNullOrEmpty($databaseCollation))
			$messages[] = 'The database collation is not set in your db config file.';

		if (StringHelper::IsNullOrEmpty($databaseType))
			$messages[] = 'The database type is not set in your db config file.';
		else
		{
			if (!in_array($databaseType, Blocks::app()->configRepo->getDatabaseSupportedTypes()))
				$messages[] = 'Blocks does not support the database type you have set in your db config file.';
		}

		if (!empty($messages))
			throw new BlocksException(implode(PHP_EOL, $messages));

		try
		{
			$connection = Blocks::app()->db;
			if (!$connection)
				$messages[] = 'There is a problem connecting to the database with the credentials supplied in your db config file.';
		}
		catch(Exception $e)
		{
			$messages[] = 'There is a problem connecting to the database with the credentials supplied in your db config file.';
		}

		if (!empty($messages))
			throw new BlocksException(implode(PHP_EOL, $messages));

		// Check to see if the prefix_info table exists.  If not, we assume it's a fresh installation.
		$infoTable = Blocks::app()->db->schema->getTable($databaseTablePrefix.'_info');

		if ($infoTable === null)
			Blocks::app()->request->redirect(Blocks::app()->createUrl('install/index'));

		return true;

	}
}
