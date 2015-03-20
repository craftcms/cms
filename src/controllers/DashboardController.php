<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\helpers\IOHelper;
use craft\app\helpers\JsonHelper;
use craft\app\helpers\StringHelper;
use craft\app\io\Zip;
use craft\app\models\GetHelp as GetHelpModel;
use craft\app\models\Widget as WidgetModel;
use craft\app\web\Controller;
use craft\app\web\UploadedFile;

/**
 * The DashboardController class is a controller that handles various dashboard related actions including managing
 * widgets, getting [[\craft\app\widgets\Feed]] feeds and sending [[\craft\app\widgets\GetHelp]] support ticket requests.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[Controller::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class DashboardController extends Controller
{
	// Public Methods
	// =========================================================================

	/**
	 * Saves a widget.
	 *
	 * @return null
	 */
	public function actionSaveUserWidget()
	{
		$this->requirePostRequest();

		$widget = new WidgetModel();
		$widget->id = Craft::$app->getRequest()->getBodyParam('widgetId');
		$widget->type = Craft::$app->getRequest()->getRequiredBodyParam('type');
		$widget->settings = Craft::$app->getRequest()->getBodyParam('types.'.$widget->type);

		// Did it save?
		if (Craft::$app->dashboard->saveUserWidget($widget))
		{
			Craft::$app->getSession()->setNotice(Craft::t('app', 'Widget saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save widget.'));
		}

		// Send the widget back to the template
		Craft::$app->getUrlManager()->setRouteParams([
			'widget' => $widget
		]);
	}

	/**
	 * Deletes a widget.
	 *
	 * @return null
	 */
	public function actionDeleteUserWidget()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$widgetId = JsonHelper::decode(Craft::$app->getRequest()->getRequiredBodyParam('id'));
		Craft::$app->dashboard->deleteUserWidgetById($widgetId);

		$this->returnJson(['success' => true]);
	}

	/**
	 * Reorders widgets.
	 *
	 * @return null
	 */
	public function actionReorderUserWidgets()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$widgetIds = JsonHelper::decode(Craft::$app->getRequest()->getRequiredBodyParam('ids'));
		Craft::$app->dashboard->reorderUserWidgets($widgetIds);

		$this->returnJson(['success' => true]);
	}

	/**
	 * Returns the items for the Feed widget.
	 *
	 * @return null
	 */
	public function actionGetFeedItems()
	{
		$this->requireAjaxRequest();

		$url = Craft::$app->getRequest()->getRequiredParam('url');
		$limit = Craft::$app->getRequest()->getParam('limit');

		$items = Craft::$app->feeds->getFeedItems($url, $limit);

		foreach ($items as &$item)
		{
			if (isset($item['date']))
			{
				$item['date'] = $item['date']->uiTimestamp();
			}
			else
			{
				unset($item['date']);
			}
		}

		$this->returnJson(['items' => $items]);
	}

	/**
	 * Creates a new support ticket for the GetHelp widget.
	 *
	 * @return null
	 */
	public function actionSendSupportRequest()
	{
		$this->requirePostRequest();

		Craft::$app->config->maxPowerCaptain();

		$success = false;
		$errors = [];
		$zipFile = null;
		$tempFolder = null;
		$widgetId = Craft::$app->getRequest()->getBodyParam('widgetId');

		$getHelpModel = new GetHelpModel();
		$getHelpModel->fromEmail = Craft::$app->getRequest()->getBodyParam('fromEmail');
		$getHelpModel->message = trim(Craft::$app->getRequest()->getBodyParam('message'));
		$getHelpModel->attachLogs = (bool) Craft::$app->getRequest()->getBodyParam('attachLogs');
		$getHelpModel->attachDbBackup = (bool) Craft::$app->getRequest()->getBodyParam('attachDbBackup');
		$getHelpModel->attachTemplates = (bool)Craft::$app->getRequest()->getBodyParam('attachTemplates');
		$getHelpModel->attachment = UploadedFile::getInstanceByName('attachAdditionalFile');

		if ($getHelpModel->validate())
		{
			$user = Craft::$app->getUser()->getIdentity();

			// Add some extra info about this install
			$message = $getHelpModel->message . "\n\n" .
				"------------------------------\n\n" .
				'Craft '.Craft::$app->getEditionName().' '.Craft::$app->version.'.'.Craft::$app->build;

			$plugins = Craft::$app->plugins->getPlugins();

			if ($plugins)
			{
				$pluginNames = [];

				foreach ($plugins as $plugin)
				{
					$pluginNames[] = $plugin->getName().' '.$plugin->getVersion().' ('.$plugin->getDeveloper().')';
				}

				$message .= "\nPlugins: ".implode(', ', $pluginNames);
			}

			$requestParamDefaults = [
				'sFirstName' => $user->getFriendlyName(),
				'sLastName' => ($user->lastName ? $user->lastName : 'Doe'),
				'sEmail' => $getHelpModel->fromEmail,
				'tNote' => $message,
			];

			$requestParams = $requestParamDefaults;

			$hsParams = [
				'helpSpotApiURL' => 'https://support.pixelandtonic.com/api/index.php'
			];

			try
			{
				if ($getHelpModel->attachLogs || $getHelpModel->attachDbBackup)
				{
					if (!$zipFile)
					{
						$zipFile = $this->_createZip();
					}

					if ($getHelpModel->attachLogs && IOHelper::folderExists(Craft::$app->path->getLogPath()))
					{
						// Grab it all.
						$logFolderContents = IOHelper::getFolderContents(Craft::$app->path->getLogPath());

						foreach ($logFolderContents as $file)
						{
							// Make sure it's a file.
							if (IOHelper::fileExists($file))
							{
								Zip::add($zipFile, $file, Craft::$app->path->getStoragePath());
							}
						}
					}

					if ($getHelpModel->attachDbBackup && IOHelper::folderExists(Craft::$app->path->getDbBackupPath()))
					{
						// Make a fresh database backup of the current schema/data. We want all data from all tables
						// for debugging.
						Craft::$app->getDb()->backup([]);

						$backups = IOHelper::getLastModifiedFiles(Craft::$app->path->getDbBackupPath(), 3);

						foreach ($backups as $backup)
						{
							if (IOHelper::getExtension($backup) == 'sql')
							{
								Zip::add($zipFile, $backup, Craft::$app->path->getStoragePath());
							}
						}
					}
				}

				if ($getHelpModel->attachment)
				{
					// If we don't have a zip file yet, create one now.
					if (!$zipFile)
					{
						$zipFile = $this->_createZip();
					}

					$tempFolder = Craft::$app->path->getTempPath().'/'.StringHelper::UUID();

					if (!IOHelper::folderExists($tempFolder))
					{
						IOHelper::createFolder($tempFolder);
					}

					$tempFile = $tempFolder.'/'.$getHelpModel->attachment->name;
					$getHelpModel->attachment->saveAs($tempFile);

					// Make sure it actually saved.
					if (IOHelper::fileExists($tempFile))
					{
						Zip::add($zipFile, $tempFile, $tempFolder);
					}
				}

				if ($getHelpModel->attachTemplates)
				{
					// If we don't have a zip file yet, create one now.
					if (!$zipFile)
					{
						$zipFile = $this->_createZip();
					}

					if (IOHelper::folderExists(Craft::$app->path->getLogPath()))
					{
						// Grab it all.
						$templateFolderContents = IOHelper::getFolderContents(Craft::$app->path->getSiteTemplatesPath());

						foreach ($templateFolderContents as $file)
						{
							// Make sure it's a file.
							if (IOHelper::fileExists($file))
							{
								$templateFolderName = IOHelper::getFolderName(Craft::$app->path->getSiteTemplatesPath(), false);
								$siteTemplatePath = Craft::$app->path->getSiteTemplatesPath();
								$tempPath = substr($siteTemplatePath, 0, (StringHelper::length($siteTemplatePath) - StringHelper::length($templateFolderName)) - 1);
								Zip::add($zipFile, $file, $tempPath);
							}
						}
					}
				}

				if ($zipFile)
				{
					$requestParams['File1_sFilename'] = 'SupportAttachment-'.IOHelper::cleanFilename(Craft::$app->getSiteName()).'.zip';
					$requestParams['File1_sFileMimeType'] = 'application/zip';
					$requestParams['File1_bFileBody'] = base64_encode(IOHelper::getFileContents($zipFile));

					// Bump the default timeout because of the attachment.
					$hsParams['callTimeout'] = 120;
				}
			}
			catch(\Exception $e)
			{
				Craft::warning('Tried to attach debug logs to a support request and something went horribly wrong: '.$e->getMessage(), __METHOD__);

				// There was a problem zipping, so reset the params and just send the email without the attachment.
				$requestParams = $requestParamDefaults;
			}

			$hsapi = new \HelpSpotAPI($hsParams);

			$result = $hsapi->requestCreate($requestParams);

			if ($result)
			{
				if ($zipFile)
				{
					if (IOHelper::fileExists($zipFile))
					{
						IOHelper::deleteFile($zipFile);
					}
				}

				if ($tempFolder)
				{
					IOHelper::clearFolder($tempFolder);
					IOHelper::deleteFolder($tempFolder);
				}

				$success = true;
			}
			else
			{
				$hsErrors = array_filter(preg_split("/(\r\n|\n|\r)/", $hsapi->errors));
				$errors = ['Support' => $hsErrors];
			}
		}
		else
		{
			$errors = $getHelpModel->getErrors();
		}

		$this->renderTemplate('_components/widgets/GetHelp/response',
			[
				'success' => $success,
				'errors' => JsonHelper::encode($errors),
				'widgetId' => $widgetId
			]
		);
	}

	// Private Methods
	// =========================================================================

	/**
	 * @return string
	 */
	private function _createZip()
	{
		$zipFile = Craft::$app->path->getTempPath().'/'.StringHelper::UUID().'.zip';
		IOHelper::createFile($zipFile);

		return $zipFile;
	}
}
