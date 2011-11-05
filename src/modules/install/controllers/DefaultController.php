<?php

class DefaultController extends BaseController
{
	function __construct($id, $module = null)
	{
		$infoTable = Blocks::app()->db->schema->getTable(Blocks::app()->config->getDatabaseTablePrefix().'_info');
		if ($infoTable !== null)
			throw new BlocksHttpException('404', 'Page not found.');

		parent::__construct($id, $module);
		$this->layout = 'installer';
	}

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
					$status = Blocks::app()->security->validatePTUserCredentialsAndKey($model->ptUserName, $model->ptPassword, Blocks::app()->config->getSiteLicenseKey(), Blocks::getEdition());

					switch ($status)
					{
						case PTAccountCredentialStatus::Invalid:
							Blocks::app()->user->setFlash('notice', 'Invalid P&T Credentials');
							Blocks::log('Invalid P&T.com credentials entered during install.');
							break;

						case LicenseKeyStatus::UnknownKey:
							Blocks::app()->user->setFlash('notice', 'Unknown Blocks License Key');
							Blocks::log('Blocks license key is not associated with the P&T account: '.$model->ptUserName);
							break;

						case LicenseKeyStatus::WrongEdition:
							Blocks::app()->user->setFlash('notice', 'Wrong Blocks Edition for License Key');
							Blocks::log('Blocks license key is registered to a different edition of Blocks that the one being installed.');
							break;

						// No net connection
						case WebServiceReturnStatus::CODE_404:
						case LicenseKeyStatus::Valid:
							// start the db install
							$dbType = strtolower(Blocks::app()->config->getDatabaseType());
							$baseSqlSchemaFile = Blocks::app()->file->set(Blocks::getPathOfAlias('application.migrations').DIRECTORY_SEPARATOR.$dbType.'_schema.sql');

							$sqlSchemaContents = $baseSqlSchemaFile->getContents();
							$sqlSchemaContents = $this->replaceTokens($sqlSchemaContents);

							$baseSqlSchemaFile->setContents(null, $sqlSchemaContents);
							$schemaQueryArr = $this->breakDownSchema($sqlSchemaContents);

							Blocks::log('Starting database schema install script.');
							foreach ($schemaQueryArr as $query)
								$this->executeSQL($query);

							$baseSqlDataFile = Blocks::app()->file->set(Blocks::getPathOfAlias('application.migrations').DIRECTORY_SEPARATOR.$dbType.'_data.sql');

							$sqlDataContents = $baseSqlDataFile->getContents();
							$sqlDataContents = $this->replaceTokens($sqlDataContents);

							$baseSqlDataFile->setContents(null, $sqlDataContents);
							$dataQueryArr = $this->breakDownSchema($sqlDataContents);

							Blocks::log('Starting database data install script.');
							foreach ($dataQueryArr as $query)
								$this->executeSQL($query);

							// register the admin.
							Blocks::app()->membership->registerUser($model->adminUserName, $model->adminEmail, $model->adminFirstName, $model->adminLastName, $model->adminPassword);

							// update the info table.
							$info = new Info();
							$info->build_number = Blocks::getBuildNumber();
							$info->edition = Blocks::getEdition();
							$info->version = Blocks::getVersion();
							$info->save();

							Blocks::app()->request->redirect('/admin.php');
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

	private function executeSQL($query)
	{
		$connection = Blocks::app()->db;

		$connection->charset = Blocks::app()->config->getDatabaseCharset();
		$connection->active = true;

		if (preg_match('/(CREATE|DROP|ALTER|SET|INSERT)/i', $query))
		{
			Blocks::log('Executing: '.$query);
			$connection->createCommand($query)->execute();
		}
	}

	private function replaceTokens($fileContents)
	{
		$fileContents = str_replace('@@@', Blocks::app()->config->getDatabaseTablePrefix(), $fileContents);
		$fileContents = str_replace('^^^', Blocks::app()->config->getDatabaseCharset(), $fileContents);
		$fileContents = str_replace('###', Blocks::app()->config->getDatabaseCollation(), $fileContents);

		return $fileContents;
	}

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
