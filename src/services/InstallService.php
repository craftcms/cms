<?php
namespace Blocks;

/**
 *
 */
class InstallService extends \CApplicationComponent
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

			Blocks::log('Populating the info table.', \CLogger::LEVEL_INFO);

			// Populate the info table
			$info = new InfoRecord();
			$info->version = Blocks::getVersion();
			$info->build = Blocks::getBuild();
			$info->releaseDate = Blocks::getReleaseDate();
			$info->siteName = $inputs['siteName'];
			$info->siteUrl = $inputs['siteUrl'];
			$info->language = $inputs['language'];
			/* BLOCKS ONLY */
			$info->licenseKey = $licenseKey;
			/* end BLOCKS ONLY */
			/* BLOCKSPRO ONLY */
			$info->licenseKey = $inputs['licensekey'];
			/* end BLOCKSPRO ONLY */
			$info->on = true;

			$info->save();

			// Something bad happened (probably a validation error)
			if ($info->hasErrors())
			{
				$errors = $info->getErrors();
				$errorMessages = implode('.  ', $errors);
				throw new Exception(Blocks::t('There was a problem saving to the info table: {errorMessages}', array('errorMessages' => $errorMessages)));
			}

			/* BLOCKSPRO ONLY */
			Blocks::log('Registering email messages.', \CLogger::LEVEL_INFO);

			$this->_registerEmailMessage('verify_email', Blocks::t('verify_email_subject'), Blocks::t('verify_email_body'));
			$this->_registerEmailMessage('verify_new_email', Blocks::t('verify_new_email_subject'), Blocks::t('verify_new_email_body'));
			$this->_registerEmailMessage('forgot_password', Blocks::t('forgot_password_subject'), Blocks::t('forgot_password_body'));
			/* end BLOCKSPRO ONLY */

			// Add the user
			$user = new UserRecord();
			$user->username   = $inputs['username'];
			$user->email      = $inputs['email'];
			$user->admin = true;
			/* BLOCKSPRO ONLY */
			$user->language = blx()->language;
			/* end BLOCKSPRO ONLY */
			blx()->accounts->changePassword($user, $inputs['password'], false);
			$user->save();

			// Log them in
			$loginForm = new LoginModel();
			$loginForm->username = $user->username;
			$loginForm->password = $inputs['password'];
			$loginForm->login();

			// Give them the default dashboard widgets
			blx()->dashboard->assignDefaultUserWidgets($user->id);

			// Save the default email settings
			blx()->systemSettings->saveSettings('email', array(
				'protocol'     => EmailerType::Php,
				'emailAddress' => $user->email,
				'senderName'   => $inputs['siteName']
			));

			/* BLOCKSPRO ONLY */
			// Create a Blog section
			$section = blx()->content->saveSection(array(
				'name'      => Blocks::t('Blog'),
				'handle'    => 'blog',
				'urlFormat' => 'blog/{slug}',
				'hasUrls'   => true,
				'template'  => 'blog/_entry'
			));

			// Give it a Body block
			blx()->content->saveEntryBlock($section->id, array(
				'name'   => Blocks::t('Body'),
				'handle' => 'body',
				'required' => true,
				'translatable' => true,
				'class' => 'PlainText',
				'settings' => array(
					'hint' => Blocks::t('Enter your blog post’s body…')
				)
			));

			/*// Add a Welcome entry to the Blog
			$entry = blx()->content->createEntry($section->id, null, $user->id, 'Welcome to Blocks Alpha 2');
			blx()->content->saveEntryContent($entry, array(
				'body' => "Hey {$user->username},\n\n" .
				          "Welcome to Blocks Alpha 2!\n\n" .
				          '-Brandon & Brad'
			));*/

			/* end BLOCKSPRO ONLY */

			$transaction->commit();
		}
		catch (\Exception $e)
		{
			$transaction->rollBack();
			throw $e;
		}
	}

	/**
	 * @param $messageKey
	 * @param $subjectKey
	 * @param $bodyKey
	 */
	private function _registerEmailMessage($messageKey, $subjectKey, $bodyKey)
	{
		// Register the email messages
		$message = blx()->email->registerMessage($messageKey);

		if (!$message->hasErrors())
		{
			// Save the message content.
			$content = blx()->email->saveMessageContent($message->id, $subjectKey, $bodyKey);

			// Problem saving content.
			if ($content->hasErrors())
			{
				$errors = $content->getErrors();
				$errorMessages = implode('.  ', $errors);
				Blocks::log('There was a problem saving email message content: '.$errorMessages, \CLogger::LEVEL_WARNING);
			}
		}
		else
		{
			// Problem registering email.
			$errors = $message->getErrors();
			$errorMessages = implode('.  ', $errors);
			Blocks::log('There was a problem registering email with key '.$messageKey.' : '.$errorMessages, \CLogger::LEVEL_WARNING);
		}
	}
}
