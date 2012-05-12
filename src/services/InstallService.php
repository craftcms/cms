<?php
namespace Blocks;

/**
 *
 */
class InstallService extends Component
{
	/**
	 * Installs Blocks!
	 * @param array $inputs
	 */
	public function run($inputs)
	{
		if (b()->isInstalled)
			throw new Exception('Blocks is already installed.');

		// Install the Block model first so the other models can create FK's to it
		$models[] = new Block;

		$modelsDir = b()->file->set(b()->path->modelsPath);
		$modelFiles = $modelsDir->getContents(false, '.php');

		foreach ($modelFiles as $filePath)
		{
			$file = b()->file->set($filePath);
			$fileName = $file->fileName;

			// Ignore Block since that's already queued up,
			// and the abstract models
			if (in_array($fileName, array('Block', 'ActiveRecord', 'Model')))
				continue;

			$class = __NAMESPACE__.'\\'.$fileName;
			$obj = new $class;

			if (method_exists($obj, 'createTable'))
				$models[] = $obj;
		}

		// Start the transaction
		$transaction = b()->db->beginTransaction();
		try
		{
			// Create the languages table first
			// This is a special case: So that other tables' language columns
			// can be restricted to supported languages without making them enums
			$table = b()->config->tablePrefix.'languages';
			$columns = array('language' => 'CHAR(5) NOT NULL PRIMARY KEY');
			b()->db->createCommand()->setText(b()->db->schema->createTable($table, $columns))->execute();

			// Add the languages
			b()->db->createCommand()->insert('languages', array('language' => 'en_us'));

			// Create the tables
			foreach ($models as $model)
			{
				Blocks::log('Creating table for model:'. get_class($model), \CLogger::LEVEL_INFO);
				$model->createTable();
			}

			// Create the foreign keys
			foreach ($models as $model)
			{
				Blocks::log('Adding foreign keys for model:'. get_class($model), \CLogger::LEVEL_INFO);
				$model->addForeignKeys();
			}

			// Tell Blocks that it's installed now
			b()->isInstalled = true;

			Blocks::log('Populating the info table.', \CLogger::LEVEL_INFO);

			// Populate the info table
			$info = new Info;
			$info->version = Blocks::getVersion(false);
			$info->build = Blocks::getBuild(false);
			$info->release_date = Blocks::getReleaseDate(false);
			$info->save();

			// Add the site
			$site = new Site;
			$site->name        = $inputs['sitename'];
			$site->url         = $inputs['url'];
			$site->language    = $inputs['language'];
			$site->handle      = 'primary';
			$site->license_key = $inputs['licensekey'];
			$site->primary     = true;
			$site->save();

			// Add the user
			$user = new User;
			$user->username   = $inputs['username'];
			$user->email      = $inputs['email'];
			$user->admin = true;
			b()->users->changePassword($user, $inputs['password'], false);
			$user->save();

			// Log them in
			$loginInfo = new LoginForm;
			$loginInfo->loginName = $user->username;
			$loginInfo->password = $inputs['password'];
			$loginInfo->login();

			// Give them the default dashboard widgets
			b()->dashboard->assignDefaultUserWidgets($user->id);

			// Save the default email settings
			b()->email->saveEmailSettings(array(
				'protocol'     => EmailerType::PhpMail,
				'emailAddress' => $user->email,
				'senderName'   => $site->name
			));

			// Create a Blog section
			$section = b()->content->saveSection(array(
				'name'       => 'Blog',
				'handle'     => 'blog',
				'url_format' => 'blog/{slug}',
				'template'   => 'blog/_entry',
				'blocks'     => array(
					'new1' => array(
						'class'    => 'PlainText',
						'name'     => 'Body',
						'handle'   => 'body',
						'required' => true,
						'settings' => array(
							'hint' => 'Enter your blog post’s body…'
						)
					)
				)
			));

			// Add a Welcome entry to the Blog
			$entry = b()->content->createEntry($section->id, null, $user->id, 'Welcome to Blocks Alpha 2');
			b()->content->saveEntryContent($entry, array(
				'body' => "Hey {$user->username},\n\n" .
				          "Welcome to Blocks Alpha 2!\n\n" .
				          '-Brandon & Brad'
			));

			// Turn the system on
			Blocks::turnSystemOn();

			$transaction->commit();
		}
		catch (\Exception $e)
		{
			$transaction->rollBack();
			throw $e;
		}
	}
}
