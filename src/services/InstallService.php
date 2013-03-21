<?php
namespace Craft;

/**
 *
 */
class InstallService extends BaseApplicationComponent
{
	/**
	 * Installs Craft!
	 *
	 * @param array $inputs
	 * @throws Exception
	 * @throws \Exception
	 * @return void
	 */
	public function run($inputs)
	{
		if (Craft::isInstalled())
		{
			throw new Exception(Craft::t('@@@appName@@@ is already installed.'));
		}

		// Set the language to the desired locale
		craft()->setLanguage($inputs['locale']);

		$records = $this->findInstallableRecords();

		// Start the transaction
		$transaction = craft()->db->beginTransaction();
		try
		{
			Craft::log('Installing Craft.');

			// Create the tables
			$this->_createTablesFromRecords($records);
			$this->_createForeignKeysFromRecords($records);
			$this->_createSearchIndexTable();

			$this->_createAndPopulateInfoTable($inputs);

			Craft::log('Committing the transaction.');
			$transaction->commit();
		}
		catch (\Exception $e)
		{
			$transaction->rollBack();
			throw $e;
		}

		// Craft, you are installed now.
		Craft::setIsInstalled();

		$this->_populateMigrationTable();
		$this->_addLocale($inputs['locale']);
		$this->_addUser($inputs);
		$this->_logUserIn($inputs);
		$this->_saveDefaultMailSettings($inputs['email'], $inputs['siteName']);
		$this->_createDefaultContent($inputs);

		Craft::log('Finished installing Craft.');
	}

	/**
	 * Finds installable records from the models folder.
	 *
	 * @return array
	 */
	public function findInstallableRecords()
	{
		$records = array();

		$recordsFolder = craft()->path->getAppPath().'records/';
		$recordFiles = IOHelper::getFolderContents($recordsFolder, false, ".*Record\.php");

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
					Craft::log("Skipping record {$file} because it’s abstract or an interface.", \CLogger::LEVEL_WARNING);
					continue;
				}

				$obj = new $class('install');

				if (method_exists($obj, 'createTable'))
				{
					$records[] = $obj;
				}
				else
				{
					Craft::log("Skipping record {$file} because it doesn’t have a createTable() method.", \CLogger::LEVEL_WARNING);
				}
			}
			else
			{
				Craft::log("Skipping record {$file} because it doesn’t exist.", \CLogger::LEVEL_WARNING);
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
			Craft::log('Creating table for record:'. get_class($record));
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
			Craft::log('Adding foreign keys for record:'. get_class($record));
			$record->addForeignKeys();
		}
	}

	/**
	 * Creates the searchindex table.
	 *
	 * @access private
	 */
	private function _createSearchIndexTable()
	{
		Craft::log('Creating the searchindex table.');

		// Taking the scenic route here so we can get to MysqlSchema's $engine argument
		$table = DbHelper::addTablePrefix('searchindex');

		$columns = array(
			'elementId' => DbHelper::generateColumnDefinition(array('column' => ColumnType::Int, 'null' => false)),
			'attribute' => DbHelper::generateColumnDefinition(array('column' => ColumnType::Varchar, 'maxLength' => 25, 'null' => false)),
			'fieldId'   => DbHelper::generateColumnDefinition(array('column' => ColumnType::Int, 'null' => false)),
			'locale'    => DbHelper::generateColumnDefinition(array('column' => ColumnType::Locale, 'null' => false)),
			'keywords'  => DbHelper::generateColumnDefinition(array('column' => ColumnType::Text, 'null' => false)),
		);

		craft()->db->createCommand()->setText(craft()->db->getSchema()->createTable($table, $columns, null, 'MyISAM'))->execute();

		// Give it a composite primary key
		craft()->db->createCommand()->addPrimaryKey('searchindex', 'elementId,attribute,fieldId,locale');

		// Add the FULLTEXT index on `keywords`
		craft()->db->createCommand()->setText('CREATE FULLTEXT INDEX ' .
			craft()->db->quoteTableName(DbHelper::getIndexName('searchindex', 'keywords')).' ON ' .
			craft()->db->quoteTableName($table).' ' .
			'('.craft()->db->quoteColumnName('keywords').')'
		)->execute();

		Craft::log('Finished creating the searchindex table.');
	}

	/**
	 * Populates the info table with install and environment information.
	 *
	 * @access private
	 * @param $inputs
	 * @throws Exception
	 */
	private function _createAndPopulateInfoTable($inputs)
	{
		Craft::log('Creating the info table.');

		craft()->db->createCommand()->createTable('info', array(
			'version'     => array('maxLength' => 15, 'column' => ColumnType::Char, 'required' => true),
			'build'       => array('maxLength' => 11, 'column' => ColumnType::Int, 'unsigned' => true, 'required' => true),
			'packages'    => array('maxLength' => 200),
			'releaseDate' => array('column' => ColumnType::DateTime, 'required' => true),
			'siteName'    => array('maxLength' => 100, 'column' => ColumnType::Varchar, 'required' => true),
			'siteUrl'     => array('maxLength' => 255, 'column' => ColumnType::Varchar, 'required' => true),
			'on'          => array('maxLength' => 1, 'default' => false, 'required' => true, 'column' => ColumnType::TinyInt, 'unsigned' => true),
			'maintenance' => array('maxLength' => 1, 'default' => false, 'required' => true, 'column' => ColumnType::TinyInt, 'unsigned' => true),
		));

		Craft::log('Finished creating the info table.');

		Craft::log('Populating the info table.');

		$info = new InfoModel(array(
			'version'     => CRAFT_VERSION,
			'build'       => CRAFT_BUILD,
			'releaseDate' => CRAFT_RELEASE_DATE,
			'siteName'    => $inputs['siteName'],
			'siteUrl'     => $inputs['siteUrl'],
			'on'          => 1,
			'maintenance' => 0,
		));

		if (Craft::saveInfo($info))
		{
			Craft::log('Info table populated successfully.');
		}
		else
		{
			Craft::log('Could not populate the info table.', \CLogger::LEVEL_ERROR);
			throw new Exception(Craft::t('There was a problem saving to the info table:').$this->_getFlattenedErrors($info->getErrors()));
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
		$migration->version = craft()->migrations->getBaseMigration();
		$migration->applyTime = DateTimeHelper::currentUTCDateTime();

		if ($migration->save())
		{
			Craft::log('Migration table populated successfully.');
		}
		else
		{
			Craft::log('Could not populate the migration table.', \CLogger::LEVEL_ERROR);
			throw new Exception(Craft::t('There was a problem saving to the migrations table:').$this->_getFlattenedErrors($migration->getErrors()));
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
		Craft::log('Adding locale.');
		craft()->db->createCommand()->insert('locales', array('locale' => $locale, 'sortOrder' => 1));
		Craft::log('Locale added successfully.');
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
		Craft::log('Creating user.');

		$user = new UserModel();

		$user->username = $inputs['username'];
		$user->newPassword = $inputs['password'];
		$user->email = $inputs['email'];
		$user->admin = true;

		if (craft()->users->saveUser($user))
		{
			Craft::log('User created successfully.');
		}
		else
		{
			Craft::log('Could not create the user.', \CLogger::LEVEL_ERROR);
			throw new Exception(Craft::t('There was a problem creating the user:').$this->_getFlattenedErrors($user->getErrors()));
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
		Craft::log('Logging in user.');

		if (craft()->userSession->login($inputs['username'], $inputs['password']))
		{
			Craft::log('User logged in successfully.');
		}
		else
		{
			Craft::log('Could not log the user in.', \CLogger::LEVEL_WARNING);
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
		Craft::log('Saving default mail settings.');

		$settings = array(
			'protocol'     => EmailerType::Php,
			'emailAddress' => $email,
			'senderName'   => $siteName
		);

		if (craft()->systemSettings->saveSettings('email', $settings))
		{
			Craft::log('Default mail settings saved successfully.');
		}
		else
		{
			Craft::log('Could not save default email settings.', \CLogger::LEVEL_WARNING);
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
		Craft::log('Creating the Default field group.');

		$group = new FieldGroupModel();
		$group->name = Craft::t('Default');

		if (craft()->fields->saveGroup($group))
		{
			Craft::log('Default field group created successfully.');
		}
		else
		{
			Craft::log('Could not save the Default field group.', \CLogger::LEVEL_WARNING);
		}

		Craft::log('Creating the Body field.');

		$field = new FieldModel();
		$field->groupId      = $group->id;
		$field->type         = 'RichText';
		$field->name         = Craft::t('Body');
		$field->handle       = 'body';
		$field->translatable = true;

		if (craft()->fields->saveField($field))
		{
			Craft::log('Body field created successfully.');
		}
		else
		{
			Craft::log('Could not save the Body field.', \CLogger::LEVEL_WARNING);
		}

		Craft::log('Creating the Blog section.');

		$layoutFields = array(
			array(
				'fieldId'   => $field->id,
				'required'  => true,
				'sortOrder' => 1
			)
		);

		$layoutTabs = array(
			array(
				'name'      => Craft::t('Content'),
				'sortOrder' => 1,
				'fields'    => $layoutFields
			)
		);

		$layout = new FieldLayoutModel();
		$layout->type = ElementType::Entry;
		$layout->setTabs($layoutTabs);
		$layout->setFields($layoutFields);

		$section = new SectionModel();
		$section->name       = Craft::t('Blog');
		$section->handle     = 'blog';
		$section->titleLabel = Craft::t('Title');
		$section->hasUrls    = true;
		$section->template   = 'blog/_entry';

		$section->setLocales(array(
			$inputs['locale'] => SectionLocaleModel::populateModel(array(
				'locale'    => $inputs['locale'],
				'urlFormat' => 'blog/{slug}',
			))
		));

		$section->setFieldLayout($layout);

		if (craft()->sections->saveSection($section))
		{
			Craft::log('Blog section created successfully.');
		}
		else
		{
			Craft::log('Could not save the Blog section.', \CLogger::LEVEL_WARNING);
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
