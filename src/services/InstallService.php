<?php
namespace Blocks;

/**
 *
 */
class InstallService extends \CApplicationComponent
{
	/**
	 * Installs Blocks!
	 * @param array $inputs
	 * @throws \Exception
	 * @throws Exception
	 */
	public function run($inputs)
	{
		if (blx()->getIsInstalled())
			throw new Exception('Blocks is already installed.');

		// Install the Block model first so the other models can create FK's to it
		$models[] = new Block();

		$modelsDir = blx()->file->set(blx()->path->getModelsPath());
		$modelFiles = $modelsDir->getContents(false, '.php');

		foreach ($modelFiles as $filePath)
		{
			$file = blx()->file->set($filePath);
			$fileName = $file->fileName;

			// Ignore Block since that's already queued up
			if ($fileName == 'Block')
				continue;

			$class = __NAMESPACE__.'\\'.$fileName;

			// Ignore abstract classes
			$ref = new \ReflectionClass($class);
			if ($ref->isAbstract())
				continue;

			$obj = new $class;

			if (method_exists($obj, 'createTable'))
				$models[] = $obj;
		}

		// Start the transaction
		$transaction = blx()->db->beginTransaction();
		try
		{
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
			blx()->setIsInstalled(true);

			Blocks::log('Populating the info table.', \CLogger::LEVEL_INFO);

			// Populate the info table
			$info = new Info();
			$info->version = Blocks::getVersion(false);
			$info->build = Blocks::getBuild(false);
			$info->release_date = Blocks::getReleaseDate(false);
			$info->on = true;
			$info->save();

			// Save the general system settings
			$generalSettings['name'] = $inputs['sitename'];
			$generalSettings['url'] = $inputs['url'];
			blx()->settings->saveSettings('systemsettings', $generalSettings, 'general');

			// Set the default language
			$languages = array(blx()->language);
			blx()->settings->saveSettings('systemsettings', $languages, 'languages');

			// Add the user
			$user = new User();
			$user->username   = $inputs['username'];
			$user->email      = $inputs['email'];
			$user->admin = true;
			$user->preferred_language = blx()->language;
			blx()->users->changePassword($user, $inputs['password'], false);
			$user->save();

			// Log them in
			$loginForm = new LoginForm();
			$loginForm->username = $user->username;
			$loginForm->password = $inputs['password'];
			$loginForm->login();

			// Give them the default dashboard widgets
			blx()->dashboard->assignDefaultUserWidgets($user->id);

			// Save the default email settings
			blx()->email->saveEmailSettings(array(
				'protocol'     => EmailerType::PhpMail,
				'emailAddress' => $user->email,
				'senderName'   => $inputs['sitename']
			));

			// Create a Blog section
			/*$section = blx()->content->saveSection(array(
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
			$entry = blx()->content->createEntry($section->id, null, $user->id, 'Welcome to Blocks Alpha 2');
			blx()->content->saveEntryContent($entry, array(
				'body' => "Hey {$user->username},\n\n" .
				          "Welcome to Blocks Alpha 2!\n\n" .
				          '-Brandon & Brad'
			));*/

			$transaction->commit();
		}
		catch (\Exception $e)
		{
			$transaction->rollBack();
			throw $e;
		}
	}
}
