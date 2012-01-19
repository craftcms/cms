<?php

/**
 *
 */
class bInstallController extends bBaseController
{
	/**
	 * @internal param $id
	 * @internal param null $module
	 */
	function init()
	{
		// Return a 404 if Blocks is installed
		if (Blocks::app()->isInstalled)
			throw new bHttpException(404);
	}

	/**
	 */
	public function actionIndex()
	{
		$model = new bInstallConfigForm();

		if (Blocks::app()->request->getPost('InstallConfigForm', null) !== null)
		{
			$model->attributes = Blocks::app()->request->getPost('InstallConfigForm');

			if ($model->validate())
			{
				try
				{
					// validate P&T credentials & license key
					$status = Blocks::app()->security->validatePTUserCredentialsAndKey($model->ptUserName, $model->ptPassword, Blocks::app()->site->licenseKeys, Blocks::getEdition());

					switch ($status)
					{
						case bPtAccountCredentialStatus::Invalid:
							Blocks::app()->user->setFlash('notice', 'Invalid P&T Credentials');
							Blocks::log('Invalid P&T.com credentials entered during install.');
							break;

						case bLicenseKeyStatus::InvalidKey:
							Blocks::app()->user->setFlash('notice', 'Unknown Blocks License Key');
							Blocks::log('Blocks license key is not associated with the P&T account: '.$model->ptUserName. ' or it is for a different edition.');
							break;

						// No net connection
						case bLicenseKeyStatus::Valid:
							// start the db install
							$baseSqlSchemaFile = Blocks::app()->file->set(Blocks::getPathOfAlias('application.migrations').DIRECTORY_SEPARATOR.'mysql_schema.sql');

							$sqlSchemaContents = $baseSqlSchemaFile->contents;
							$sqlSchemaContents = $this->replaceTokens($sqlSchemaContents);

							$baseSqlSchemaFile->setContents(null, $sqlSchemaContents);
							$schemaQueryArr = $this->breakDownSchema($sqlSchemaContents);

							Blocks::log('Starting database schema install script.');
							foreach ($schemaQueryArr as $query)
								$this->executeSQL($query);

							$baseSqlDataFile = Blocks::app()->file->set(Blocks::getPathOfAlias('application.migrations').DIRECTORY_SEPARATOR.'mysql_data.sql');

							$sqlDataContents = $baseSqlDataFile->contents;
							$sqlDataContents = $this->replaceTokens($sqlDataContents);

							$baseSqlDataFile->setContents(null, $sqlDataContents);
							$dataQueryArr = $this->breakDownSchema($sqlDataContents);

							Blocks::log('Starting database data install script.');
							foreach ($dataQueryArr as $query)
								$this->executeSQL($query);

							// register the admin.
							Blocks::app()->users->registerUser($model->adminUserName, $model->adminEmail, $model->adminFirstName, $model->adminLastName, $model->adminPassword, false);

							// update the info table.
							$info = new Info();
							$info->build = Blocks::getBuild();
							$info->version = Blocks::getVersion();
							$info->save();

							// insert license key(s) into LicenseKeys table.

							Blocks::app()->request->redirect(Blocks::app()->urlManager->baseUrl);
							break;
					}
				}
				catch(Exception $e)
				{
					Blocks::log($e->getMessage());
					throw new bException('There was a problem installing Blocks: '.$e->getMessage());
				}
			}
		}

		$model->checkRequirements();
		$this->loadTemplate('install', array('model' => $model));
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
