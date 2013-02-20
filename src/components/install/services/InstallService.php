<?php
namespace Blocks;

/**
 *
 */
class InstallService extends BaseApplicationComponent
{
	/**
	 * Installs Blocks!
	 *
	 * @param array $inputs
	 * @throws Exception
	 * @throws \Exception
	 * @return void
	 */
	public function run($inputs)
	{
		if (blx()->isInstalled())
		{
			throw new Exception(Blocks::t('Blocks is already installed.'));
		}

		// Set the language to the desired locale
		blx()->setLanguage($inputs['locale']);

		$records = $this->findInstallableRecords();

		// Start the transaction
		$transaction = blx()->db->beginTransaction();
		try
		{
			Blocks::log('Installing Blocks.');

			// Create the tables
			$this->_createTablesFromRecords($records);
			$this->_createForeignKeysFromRecords($records);

			Blocks::log('Committing the transaction.');
			$transaction->commit();
		}
		catch (\Exception $e)
		{
			$transaction->rollBack();
			throw $e;
		}

		// Blocks, you are installed now.
		blx()->setInstalledStatus(true);

		// Fill 'er up
		$this->_populateInfoTable($inputs);

		// Invalidate cached info after populating the info table.
		Blocks::invalidateCachedInfo();

		$this->_populateMigrationTable();
		$this->_addLocale($inputs['locale']);
		$this->_addUser($inputs);
		$this->_logUserIn($inputs);
		$this->_saveDefaultMailSettings($inputs['email'], $inputs['siteName']);
		$this->_createDefaultContent($inputs);

		Blocks::log('Finished installing Blocks.');
	}

	/**
	 * Finds installable records from the models folder.
	 *
	 * @return array
	 */
	public function findInstallableRecords()
	{
		$records = array();
		$recordFiles = array();
		$componentsPath = blx()->path->getComponentsPath();
		$componentsFolders = IOHelper::getFolderContents($componentsPath, false);

		foreach ($componentsFolders as $componentsFolder)
		{
			// Make sure it's a folder and it has a records subfolder.
			if (IOHelper::folderExists($componentsFolder.'/records/'))
			{
				$recordFiles = array_merge($recordFiles, IOHelper::getFolderContents($componentsFolder.'/records/', false, ".*Record\.php"));
			}
		}

		foreach ($recordFiles as $file)
		{
			if (IOHelper::fileExists($file))
			{
				$fileName = IOHelper::getFileName($file, false);
				$class = __NAMESPACE__.'\\'.$fileName;

				// Ignore abstract classes and interfaces
				$ref = new \ReflectionClass($class);
				if ($ref->isAbstract() || $ref->isInterface())
				{
					Blocks::log("Skipping record {$file} because it’s abstract or an interface.", \CLogger::LEVEL_WARNING);
					continue;
				}

				$obj = new $class('install');

				if (method_exists($obj, 'createTable'))
				{
					$records[] = $obj;
				}
				else
				{
					Blocks::log("Skipping record {$file} because it doesn’t have a createTable() method.", \CLogger::LEVEL_WARNING);
				}
			}
			else
			{
				Blocks::log("Skipping record {$file} because it doesn’t exist.", \CLogger::LEVEL_WARNING);
			}
		}

		return $records;
	}

	/**
	 * Creates the tables as defined in the records.
	 *
	 * @access private
	 * @param $records
	 * @return void
	 */
	private function _createTablesFromRecords($records)
	{
		foreach ($records as $record)
		{
			Blocks::log('Creating table for record:'. get_class($record));
			$record->createTable();
		}
	}

	/**
	 * Creates the foreign keys as defined in the records.
	 *
	 * @access private
	 * @param $records
	 */
	private function _createForeignKeysFromRecords($records)
	{
		foreach ($records as $record)
		{
			Blocks::log('Adding foreign keys for record:'. get_class($record));
			$record->addForeignKeys();
		}
	}

	/**
	 * Populates the info table with install and environment information.
	 *
	 * @access private
	 * @param $inputs
	 * @throws Exception
	 */
	private function _populateInfoTable($inputs)
	{
		Blocks::log('Populating the info table.');

		$info = new InfoRecord();

		$info->version = Blocks::getVersion();
		$info->build = Blocks::getBuild();
		$info->releaseDate = Blocks::getReleaseDate();
		$info->packages = implode(',', Blocks::getPackages());
		$info->siteName = $inputs['siteName'];
		$info->siteUrl = $inputs['siteUrl'];
		$info->licenseKey = $inputs['licenseKey'];
		$info->on = true;
		$info->maintenance = false;

		if ($info->save())
		{
			Blocks::log('Info table populated successfully.');
		}
		else
		{
			Blocks::log('Could not populate the info table.', \CLogger::LEVEL_ERROR);
			throw new Exception(Blocks::t('There was a problem saving to the info table:').$this->_getFlattenedErrors($info->getErrors()));
		}
	}

	/**
	 * Populates the migrations table with the base migration.
	 *
	 * @throws Exception
	 */
	private function _populateMigrationTable()
	{
		$migration = new MigrationRecord();
		$migration->version = blx()->migrations->getBaseMigration();
		$migration->applyTime = DateTimeHelper::currentUTCDateTime();

		if ($migration->save())
		{
			Blocks::log('Migration table populated successfully.');
		}
		else
		{
			Blocks::log('Could not populate the migration table.', \CLogger::LEVEL_ERROR);
			throw new Exception(Blocks::t('There was a problem saving to the migrations table:').$this->_getFlattenedErrors($migration->getErrors()));
		}
	}

	/**
	 * Adds the initial locale to the database.
	 *
	 * @access private
	 * @param string $locale
	 */
	private function _addLocale($locale)
	{
		Blocks::log('Adding locale.');
		blx()->db->createCommand()->insert('locales', array('locale' => $locale, 'sortOrder' => 1));
		Blocks::log('Locale added successfully.');
	}

	/**
	 * Adds the initial user to the database.
	 *
	 * @access private
	 * @param $inputs
	 * @return UserModel
	 * @throws Exception
	 */
	private function _addUser($inputs)
	{
		Blocks::log('Creating user.');

		$user = new UserModel();

		$user->username = $inputs['username'];
		$user->newPassword = $inputs['password'];
		$user->email = $inputs['email'];
		$user->admin = true;

		if (blx()->users->saveUser($user))
		{
			Blocks::log('User created successfully.');
		}
		else
		{
			Blocks::log('Could not create the user.', \CLogger::LEVEL_ERROR);
			throw new Exception(Blocks::t('There was a problem creating the user:').$this->_getFlattenedErrors($user->getErrors()));
		}
	}

	/**
	 * Attempts to log in the given user.
	 *
	 * @access private
	 * @param array $inputs
	 */
	private function _logUserIn($inputs)
	{
		Blocks::log('Logging in user.');

		if (blx()->userSession->login($inputs['username'], $inputs['password']))
		{
			Blocks::log('User logged in successfully.');
		}
		else
		{
			Blocks::log('Could not log the user in.', \CLogger::LEVEL_WARNING);
		}
	}

	/**
	 * Saves some default mail settings for the site.
	 *
	 * @access private
	 * @param $email
	 * @param $siteName
	 */
	private function _saveDefaultMailSettings($email, $siteName)
	{
		Blocks::log('Saving default mail settings.');

		$settings = array(
			'protocol'     => EmailerType::Php,
			'emailAddress' => $email,
			'senderName'   => $siteName
		);

		if (blx()->systemSettings->saveSettings('email', $settings))
		{
			Blocks::log('Default mail settings saved successfully.');
		}
		else
		{
			Blocks::log('Could not save default email settings.', \CLogger::LEVEL_WARNING);
		}
	}

	/**
	 * Creates initial database content for the install.
	 *
	 * @access private
	 * @return null
	 */
	private function _createDefaultContent($inputs)
	{
		Blocks::log('Creating the Default field group.');

		$group = new FieldGroupModel();
		$group->name = Blocks::t('Default');

		if (blx()->fields->saveGroup($group))
		{
			Blocks::log('Default field group created successfully.');
		}
		else
		{
			Blocks::log('Could not save the Default field group.', \CLogger::LEVEL_WARNING);
		}

		Blocks::log('Creating the Body field.');

		$field = new FieldModel();
		$field->groupId      = $group->id;
		$field->type         = 'RichText';
		$field->name         = Blocks::t('Body');
		$field->handle       = 'body';
		$field->translatable = true;

		if (blx()->fields->saveField($field))
		{
			Blocks::log('Body field created successfully.');
		}
		else
		{
			Blocks::log('Could not save the Body field.', \CLogger::LEVEL_WARNING);
		}

		Blocks::log('Creating the Blog section.');

		$layoutFields = array(
			array(
				'fieldId'   => $field->id,
				'required'  => true,
				'sortOrder' => 1
			)
		);

		$layoutTabs = array(
			array(
				'name'      => Blocks::t('Content'),
				'sortOrder' => 1,
				'fields'    => $layoutFields
			)
		);

		$layout = new FieldLayoutModel();
		$layout->type = 'SectionEntry';
		$layout->setTabs($layoutTabs);
		$layout->setFields($layoutFields);

		$section = new SectionModel();
		$section->name       = Blocks::t('Blog');
		$section->handle     = 'blog';
		$section->titleLabel = Blocks::t('Title');
		$section->hasUrls    = true;
		$section->template   = 'blog/_entry';

		$section->setLocales(array(
			$inputs['locale'] => SectionLocaleModel::populateModel(array(
				'locale'    => $inputs['locale'],
				'urlFormat' => 'blog/{slug}',
			))
		));

		$section->setFieldLayout($layout);

		if (blx()->sections->saveSection($section))
		{
			Blocks::log('Blog section created successfully.');
		}
		else
		{
			Blocks::log('Could not save the Blog section.', \CLogger::LEVEL_WARNING);
		}
	}

	/**
	 * Get a flattened list of model errors
	 *
	 * @access private
	 * @param array $errors
	 * @return string
	 */
	private function _getFlattenedErrors($errors)
	{
		$return = '';

		foreach ($errors as $attribute => $attributeErrors)
		{
			$return .= "\n - ".implode("\n - ", $attributeErrors);
		}

		return $return;
	}
}
