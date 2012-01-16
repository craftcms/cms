<?php

/**
 *
 */
class InstallController extends BaseController
{
	/**
	 * @param      $id
	 * @param null $module
	 */
	function __construct($id, $module = null)
	{
		if (!Blocks::app()->config('devMode'))
		{
			$infoTable = Blocks::app()->db->schema->getTable('{{info}}');
			if ($infoTable !== null)
				throw new BlocksHttpException(404);
		}

		parent::__construct($id, $module);
		$this->layout = 'installer';
	}

	/**
	 */
	public function actionIndex()
	{
		$model = new InstallConfigForm();

		if(Blocks::app()->request->getPost('InstallConfigForm', null) !== null)
		{
			$model->attributes = Blocks::app()->request->getPost('InstallConfigForm');

			if($model->validate())
			{
				try
				{
					// validate P&T credentials & license key
					$status = Blocks::app()->security->validatePTUserCredentialsAndKey($model->ptUserName, $model->ptPassword, Blocks::app()->site->licenseKeys, Blocks::getEdition());

					switch ($status)
					{
						case PTAccountCredentialStatus::Invalid:
							Blocks::app()->user->setFlash('notice', 'Invalid P&T Credentials');
							Blocks::log('Invalid P&T.com credentials entered during install.');
							break;

						case LicenseKeyStatus::InvalidKey:
							Blocks::app()->user->setFlash('notice', 'Unknown Blocks License Key');
							Blocks::log('Blocks license key is not associated with the P&T account: '.$model->ptUserName. ' or it is for a different edition.');
							break;

						// No net connection
						case LicenseKeyStatus::Valid:
							// start the db install
							$dbType = strtolower(Blocks::app()->config->databaseType);
							$baseSqlSchemaFile = Blocks::app()->file->set(Blocks::getPathOfAlias('application.migrations').DIRECTORY_SEPARATOR.$dbType.'_schema.sql');

							$sqlSchemaContents = $baseSqlSchemaFile->contents;
							$sqlSchemaContents = $this->replaceTokens($sqlSchemaContents);

							$baseSqlSchemaFile->setContents(null, $sqlSchemaContents);
							$schemaQueryArr = $this->breakDownSchema($sqlSchemaContents);

							Blocks::log('Starting database schema install script.');
							foreach ($schemaQueryArr as $query)
								$this->executeSQL($query);

							$baseSqlDataFile = Blocks::app()->file->set(Blocks::getPathOfAlias('application.migrations').DIRECTORY_SEPARATOR.$dbType.'_data.sql');

							$sqlDataContents = $baseSqlDataFile->contents;
							$sqlDataContents = $this->replaceTokens($sqlDataContents);

							$baseSqlDataFile->setContents(null, $sqlDataContents);
							$dataQueryArr = $this->breakDownSchema($sqlDataContents);

							Blocks::log('Starting database data install script.');
							foreach ($dataQueryArr as $query)
								$this->executeSQL($query);

							// register the admin.
							Blocks::app()->users->registerUser($model->adminUserName, $model->adminEmail, $model->adminFirstName, $model->adminLastName, $model->adminPassword);

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
					throw new BlocksException('There was a problem installing Blocks: '.$e->getMessage());
				}
			}
		}

		$model->checkRequirements();
		$this->render('index', array('model' => $model));
	}

	/**
	 * @access private
	 * @param $query
	 */
	private function executeSQL($query)
	{
		$connection = Blocks::app()->db;

		$connection->charset = Blocks::app()->config->databaseCharset;
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
		$fileContents = str_replace('@@@', Blocks::app()->config->databaseTablePrefix(), $fileContents);
		$fileContents = str_replace('^^^', Blocks::app()->config->databaseCharset(), $fileContents);
		$fileContents = str_replace('###', Blocks::app()->config->databaseCollation(), $fileContents);

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
