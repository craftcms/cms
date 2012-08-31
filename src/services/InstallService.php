<?php
namespace Blocks;

/**
 *
 */
class InstallService extends \CApplicationComponent
{
	/**
	 * Installs @@@productDisplay@@@!
	 * @param array $inputs
	 * @throws Exception
	 * @throws \Exception
	 * @return void
	 */
	public function run($inputs)
	{
		if (blx()->getIsInstalled())
			throw new Exception(Blocks::t('@@@productDisplay@@@ is already installed.'));

		$models = array();
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

			// Ignore abstract classes and interfaces
			$ref = new \ReflectionClass($class);
			if ($ref->isAbstract() || $ref->isInterface())
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

			// Tell @@@productDisplay@@@ that it's installed now
			blx()->setIsInstalled(true);

			Blocks::log('Populating the info table.', \CLogger::LEVEL_INFO);

			/* BLOCKS ONLY */
			// Generate a license key
			$licenseKey = strtoupper(sprintf('%04x-%04x-%04x-%04x-%04x-%04x',
				mt_rand(0, 0xffff),
				mt_rand(0, 0xffff),
				mt_rand(0, 0xffff),
				mt_rand(0, 0xffff),
				mt_rand(0, 0xffff),
				mt_rand(0, 0xffff)
			));
			/* end BLOCKS ONLY */

			// Populate the info table
			$info = new Info();
			$info->version = Blocks::getVersion();
			$info->build = Blocks::getBuild();
			$info->release_date = Blocks::getReleaseDate();
			$info->site_name = $inputs['sitename'];
			$info->site_url = $inputs['url'];
			$info->language = $inputs['language'];
			/* BLOCKS ONLY */
			$info->license_key = $licenseKey;
			/* end BLOCKS ONLY */
			/* BLOCKSPRO ONLY */
			$info->license_key = $inputs['licensekey'];
			/* end BLOCKSPRO ONLY */
			$info->on = true;
			$info->save();

			/* BLOCKSPRO ONLY */
			// Register the email messages
			$message = blx()->email->registerMessage('verify_email');
			blx()->email->saveMessageContent($message->id,
				Blocks::t('verify_email_subject'),
				Blocks::t('verify_email_body'));

			$message = blx()->email->registerMessage('verify_new_email');
			blx()->email->saveMessageContent($message->id,
				Blocks::t('verify_new_email_subject'),
				Blocks::t('verify_new_email_body'));

			$message = blx()->email->registerMessage('forgot_password');
			blx()->email->saveMessageContent($message->id,
				Blocks::t('forgot_password_subject'),
				Blocks::t('forgot_password_body'));
			/* end BLOCKSPRO ONLY */

			// Add the user
			$user = new User();
			$user->username   = $inputs['username'];
			$user->email      = $inputs['email'];
			$user->admin = true;
			/* BLOCKSPRO ONLY */
			$user->language = blx()->language;
			/* end BLOCKSPRO ONLY */
			blx()->accounts->changePassword($user, $inputs['password'], false);
			$user->save();

			// Log them in
			$loginForm = new LoginForm();
			$loginForm->username = $user->username;
			$loginForm->password = $inputs['password'];
			$loginForm->login();

			// Give them the default dashboard widgets
			blx()->dashboard->assignDefaultUserWidgets($user->id);

			// Save the default email settings
			blx()->email->saveSettings(array(
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
