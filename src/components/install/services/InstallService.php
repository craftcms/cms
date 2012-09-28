<?php
namespace Blocks;

/**
 *
 */
class InstallService extends BaseApplicationComponent
{
	/**
	 * Installs @@@productDisplay@@@!
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
			throw new Exception(Blocks::t('@@@productDisplay@@@ is already installed.'));
		}

		$records = $this->_findInstallableRecords();

		// Start the transaction
		$transaction = blx()->db->beginTransaction();
		try
		{
			// Create the tables
			$this->_createTablesFromRecords($records);

			// Create the foreign keys
			$this->_createForeignKeysFromRecords($records);

			if (Blocks::hasPackage(BlocksPackage::Users))
			{
				// Create the usergroups_users join table
				$this->_createUsergroupsUsersTable();
			}

			// Tell @@@productDisplay@@@ that it's installed now
			blx()->setInstalledStatus(true);

			Blocks::log('Populating the info table.', \CLogger::LEVEL_INFO);
			$this->_populateInfoTable($inputs);

			Blocks::log('Creating user.', \CLogger::LEVEL_INFO);
			$user = $this->_addUser($inputs);

			Blocks::log('Logging in user.', \CLogger::LEVEL_INFO);
			$this->_logUserIn($user, $inputs['password']);

			Blocks::log('Assigning default dashboard widgets to user.', \CLogger::LEVEL_INFO);
			blx()->dashboard->addDefaultUserWidgets();

			Blocks::log('Saving default mail settings.', \CLogger::LEVEL_INFO);
			$this->_saveDefaultMailSettings($user->email, $inputs['siteName']);

			$this->_createDefaultContent();

			Blocks::log('Finished installing... committing transaction.', \CLogger::LEVEL_INFO);
			$transaction->commit();
		}
		catch (\Exception $e)
		{
			$transaction->rollBack();
			throw $e;
		}
	}

	/**
	 * Finds installable records from the models folder.
	 *
	 * @access private
	 * @return array
	 */
	private function _findInstallableRecords()
	{
		$records = array();
		$componentsDir = IOHelper::getFolder(blx()->path->getComponentsPath());
		$recordFiles = $componentsDir->getContents(true, "\/records\/.*Record\.php");

		foreach ($recordFiles as $file)
		{
			if (IOHelper::fileExists($file))
			{
				$fileName = IOHelper::getFileName($file, false);

				if (Blocks::hasPackage(BlocksPackage::PublishPro))
				{
					// Skip EntryContentRecord and SectionContentRecord
					if ($fileName == 'EntryContentRecord' || $fileName == 'SectionContentRecord')
					{
						continue;
					}
				}

				$class = __NAMESPACE__.'\\'.$fileName;

				// Ignore abstract classes and interfaces
				$ref = new \ReflectionClass($class);
				if ($ref->isAbstract() || $ref->isInterface())
				{
					continue;
				}

				$obj = new $class('install');

				if (method_exists($obj, 'createTable'))
				{
					$records[] = $obj;
				}
			}
		}

		return $records;
	}

	/**
	 * Attempts to log in the given user.
	 *
	 * @access private
	 * @param $user
	 * @param $password
	 * @return void
	 */
	private function _logUserIn($user, $password)
	{
		$loginModel = new LoginModel();
		$loginModel->username = $user->username;
		$loginModel->password = $password;

		if (!$loginModel->login())
		{
			Blocks::log('Could not log the user in during install.', \CLogger::LEVEL_ERROR);
		}
	}

	/**
	 * Adds the initial user to the database.
	 *
	 * @access private
	 * @param $inputs
	 * @return UserRecord
	 * @throws Exception
	 */
	private function _addUser($inputs)
	{
		$user = new UserRecord();
		$user->username   = $inputs['username'];
		$user->email      = $inputs['email'];
		$user->admin = true;
		$user->language = blx()->language;
		blx()->account->changePassword($user, $inputs['password'], false);
		$user->save();

		if ($user->hasErrors())
		{
			$errors = $user->getErrors();
			$errorMessages = implode('.  ', $errors);
			throw new Exception(Blocks::t('There was a problem creating the user: {errorMessages}', array('errorMessages' => $errorMessages)));
		}

		return $user;
	}

	/**
	 * Creates the tables as defined in the records.
	 *
	 * @access private
	 * @param $records
	 */
	private function _createTablesFromRecords($records)
	{
		foreach ($records as $record)
		{
			Blocks::log('Creating table for record:'. get_class($record), \CLogger::LEVEL_INFO);
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
			Blocks::log('Adding foreign keys for record:'. get_class($record), \CLogger::LEVEL_INFO);
			$record->addForeignKeys();
		}
	}

	/**
	 * Creates the usergroups_users join table.
	 *
	 * @access private
	 */
	private function _createUsergroupsUsersTable()
	{
		if (Blocks::hasPackage(BlocksPackage::Users))
		{
			blx()->db->createCommand()->createTable('usergroups_users', array(
				'groupId' => array('column' => ColumnType::Int, 'required' => true),
				'userId'  => array('column' => ColumnType::Int, 'required' => true)
			));

			blx()->db->createCommand()->addForeignKey('usergroups_users_group_fk', 'usergroups_users', 'groupId', 'usergroups', 'id');
			blx()->db->createCommand()->addForeignKey('usergroups_users_user_fk', 'usergroups_users', 'userId', 'users', 'id');
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
		$success = blx()->systemSettings->saveSettings('email', array(
			'protocol'     => EmailerType::Php,
			'emailAddress' => $email,
			'senderName'   => $siteName
		));

		if (!$success)
		{
			Blocks::log('Could not save default email settings.', \CLogger::LEVEL_ERROR);
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
		$info = new InfoRecord();
		$info->version = Blocks::getVersion();
		$info->build = Blocks::getBuild();
		$info->releaseDate = Blocks::getReleaseDate();
		$info->siteName = $inputs['siteName'];
		$info->siteUrl = $inputs['siteUrl'];
		$info->language = $inputs['language'];
		$info->licenseKey = $this->_generateLicenseKey();
		$info->on = true;

		$info->save();

		// Something bad happened (probably a validation error)
		if ($info->hasErrors())
		{
			$errors = $info->getErrors();
			$errorMessages = implode('.  ', $errors);
			throw new Exception(Blocks::t('There was a problem saving to the info table: {errorMessages}', array('errorMessages' => $errorMessages)));
		}
	}

	/**
	 * Creates initial database content for the install.
	 *
	 * @access private
	 * @return null
	 */
	private function _createDefaultContent()
	{
		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			Blocks::log('Creating default "Blog" section."', \CLogger::LEVEL_INFO);
			$section = new SectionPackage();
			$section->name = Blocks::t('Blog');
			$section->handle = 'blog';
			$section->hasUrls = true;
			$section->urlFormat = 'blog/{slug}';
			$section->template = 'blog/_entry';
			blx()->sections->saveSection($section);
		}

		Blocks::log('Giving "Blog" section a "Body" block.', \CLogger::LEVEL_INFO);

		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			$block = new SectionBlockPackage();
			$block->sectionId = $section->id;
		}
		else
		{
			$block = new EntryBlockPackage();
		}

		$block->name = Blocks::t('Body');
		$block->handle = 'body';
		$block->required = true;
		$block->type = 'RichText';

		if (Blocks::hasPackage(BlocksPackage::Language))
		{
			$block->translatable = true;
		}

		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			blx()->sectionBlocks->saveBlock($block);
		}
		else
		{
			blx()->entryBlocks->saveBlock($block);
		}


		/*// Add a Welcome entry to the Blog
		$entry = blx()->entries->createEntry($section->id, null, $user->id, 'Welcome to Blocks Alpha 2');
		blx()->entries->saveEntryContent($entry, array(
			'body' => "Hey {$user->username},\n\n" .
			          "Welcome to Blocks Alpha 2!\n\n" .
			          '-Brandon & Brad'
		));*/
	}

	/**
	 * Generates a license key.
	 *
	 * @access private
	 * @return string
	 */
	private function _generateLicenseKey()
	{
		$licenseKey = strtoupper(sprintf('%04x-%04x-%04x-%04x-%04x-%04x',
			mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),
			mt_rand(0, 0xffff)
		));

		return $licenseKey;
	}
}
