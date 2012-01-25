<?php

/**
 *
 */
class bInstallController extends bBaseController
{
	public function init()
	{
		// Return a 404 if Blocks is already installed
		if (!Blocks::app()->getConfig('devMode') && Blocks::app()->install->isBlocksInstalled)
			throw new bHttpException(404);
	}

	/**
	 */
	public function actionIndex()
	{
		$reqCheck = new bRequirementsChecker();
		$reqCheck->run();

		if ($reqCheck->result !== bInstallStatus::Failure)
			$this->loadTemplate('install');
		else
			$this->loadTemplate('install/cantinstall', array('requirements' => $reqCheck->requirements));
	}

	public function actionInstall()
	{
		// This must be a POST request
		$this->requirePostRequest();

		// Run the installer
		Blocks::app()->install->installBlocks();

		// TODO: redirect to the setup wizard
		die('Blocks is installed!');
	}

	/**
	 * @access private
	 * @param $query
	 */
	private function executeSQL($query)
	{
		$connection = Blocks::app()->db;

		$connection->charset = Blocks::app()->getDbConfig('charset');
		$connection->active = true;

		if (preg_match('/(CREATE|DROP|ALTER|SET|INSERT)/i', $query))
		{
			Blocks::log('Executing: '.$query);
			$connection->createCommand($query)->execute();
		}
	}

	/**
	 * @access private
	 * @param $fileContents
	 * @return mixed
	 */
	private function replaceTokens($fileContents)
	{
		$fileContents = str_replace('@@@', Blocks::app()->getDbConfig('tablePrefix'), $fileContents);
		$fileContents = str_replace('^^^', Blocks::app()->getDbConfig('charset'), $fileContents);
		$fileContents = str_replace('###', Blocks::app()->getDbConfig('collation'), $fileContents);

		return $fileContents;
	}

	/**
	 * @access private
	 * @param $schema
	 * @return array
	 */
	private function breakDownSchema($schema)
	{
		$buffer = array();
		$queryArr = array();
		$inString = false;

		// Trim any whitespace.
		$schema = trim($schema);

		// Remove any comment lines.  Accounts for -- and /* */
		$schema = preg_replace("/--[^\n]*/", '', $schema);
		$schema = preg_replace("/\/\*(.|[\r\n])*?\*\//", '', $schema);

		// Parse the schema file to get individual queries.
		for ($counter = 0; $counter < strlen($schema) - 1; $counter++)
		{
			// We found a query.  Add it to the list.
			if ($schema[$counter] == ';' && !$inString && $schema[$counter + 1] != ';')
			{
				$queryArr[] = substr($schema, 0, $counter + 1);
				$schema = substr($schema, $counter + 1);
				$counter = 0;
			}

			if ($inString && ($schema[$counter] == $inString) && $buffer[1] != '\\')
				$inString = false;
			else
			{
				if (!$inString && ($schema[$counter] == '"' || $schema[$counter] == '\'') && (!isset ($buffer[0]) || $buffer[0] != '\\'))
					$inString = $schema[$counter];
			}

			if (isset ($buffer[1]))
				$buffer[0] = $buffer[1];

			$buffer[1] = $schema[$counter];
		}

		// Add the remaining SQL to the query list.
		if (!empty($schema))
			$queryArr[] = $schema;

		return $queryArr;
	}
}
