<?php
namespace Craft;

/**
 * The DashboardController class is a controller that handles various dashboardrelated actions including managing
 * widgets, getting {@link FeedWidget} feeds and sending {@link GetHelpWidget} support ticket requests.
 *
 * Note that all actions in the controller require an authenticated Craft session via {@link BaseController::allowAnonymous}.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.controllers
 * @since     1.0
 */
class DashboardController extends BaseController
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
		$widget->id = craft()->request->getPost('widgetId');
		$widget->type = craft()->request->getRequiredPost('type');
		$widget->settings = craft()->request->getPost('types.'.$widget->type);

		// Did it save?
		if (craft()->dashboard->saveUserWidget($widget))
		{
			craft()->userSession->setNotice(Craft::t('Widget saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t save widget.'));
		}

		// Send the widget back to the template
		craft()->urlManager->setRouteVariables(array(
			'widget' => $widget
		));
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

		$widgetId = JsonHelper::decode(craft()->request->getRequiredPost('id'));
		craft()->dashboard->deleteUserWidgetById($widgetId);

		$this->returnJson(array('success' => true));
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

		$widgetIds = JsonHelper::decode(craft()->request->getRequiredPost('ids'));
		craft()->dashboard->reorderUserWidgets($widgetIds);

		$this->returnJson(array('success' => true));
	}

	/**
	 * Returns the items for the Feed widget.
	 *
	 * @return null
	 */
	public function actionGetFeedItems()
	{
		$this->requireAjaxRequest();

		$url = craft()->request->getRequiredParam('url');
		$limit = craft()->request->getParam('limit');

		$items = craft()->feeds->getFeedItems($url, $limit);

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

		$this->returnJson(array('items' => $items));
	}

	/**
	 * Creates a new support ticket for the GetHelp widget.
	 *
	 * @return null
	 */
	public function actionSendSupportRequest()
	{
		$this->requirePostRequest();

		craft()->config->maxPowerCaptain();

		$success = false;
		$errors = array();
		$zipFile = null;
		$tempFolder = null;
		$widgetId = craft()->request->getPost('widgetId');

		$getHelpModel = new GetHelpModel();
		$getHelpModel->fromEmail = craft()->request->getPost('fromEmail');
		$getHelpModel->message = trim(craft()->request->getPost('message'));
		$getHelpModel->attachLogs = (bool) craft()->request->getPost('attachLogs');
		$getHelpModel->attachDbBackup = (bool) craft()->request->getPost('attachDbBackup');
		$getHelpModel->attachTemplates = (bool)craft()->request->getPost('attachTemplates');
		$getHelpModel->attachment = UploadedFile::getInstanceByName('attachAdditionalFile');

		if ($getHelpModel->validate())
		{
			$user = craft()->userSession->getUser();

			// Add some extra info about this install
			$message = $getHelpModel->message . "\n\n" .
				"------------------------------\n\n" .
				'Craft '.craft()->getEditionName().' '.craft()->getVersion().'.'.craft()->getBuild();

			$plugins = craft()->plugins->getPlugins();

			if ($plugins)
			{
				$pluginNames = array();

				foreach ($plugins as $plugin)
				{
					$pluginNames[] = $plugin->getName().' '.$plugin->getVersion().' ('.$plugin->getDeveloper().')';
				}

				$message .= "\nPlugins: ".implode(', ', $pluginNames);
			}

			$requestParamDefaults = array(
				'sFirstName' => $user->getFriendlyName(),
				'sLastName' => ($user->lastName ? $user->lastName : 'Doe'),
				'sEmail' => $getHelpModel->fromEmail,
				'tNote' => $message,
			);

			$requestParams = $requestParamDefaults;

			$hsParams = array(
				'helpSpotApiURL' => 'https://support.pixelandtonic.com/api/index.php'
			);

			try
			{
				if ($getHelpModel->attachLogs || $getHelpModel->attachDbBackup)
				{
					if (!$zipFile)
					{
						$zipFile = $this->_createZip();
					}

					if ($getHelpModel->attachLogs && IOHelper::folderExists(craft()->path->getLogPath()))
					{
						// Grab it all.
						$logFolderContents = IOHelper::getFolderContents(craft()->path->getLogPath());

						foreach ($logFolderContents as $file)
						{
							// Make sure it's a file.
							if (IOHelper::fileExists($file))
							{
								Zip::add($zipFile, $file, craft()->path->getStoragePath());
							}
						}
					}

					if ($getHelpModel->attachDbBackup && IOHelper::folderExists(craft()->path->getDbBackupPath()))
					{
						// Make a fresh database backup of the current schema/data. We want all data from all tables
						// for debugging.
						craft()->db->backup();

						$backups = IOHelper::getLastModifiedFiles(craft()->path->getDbBackupPath(), 3);

						foreach ($backups as $backup)
						{
							if (IOHelper::getExtension($backup) == 'sql')
							{
								Zip::add($zipFile, $backup, craft()->path->getStoragePath());
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

					$tempFolder = craft()->path->getTempPath().StringHelper::UUID().'/';

					if (!IOHelper::folderExists($tempFolder))
					{
						IOHelper::createFolder($tempFolder);
					}

					$tempFile = $tempFolder.$getHelpModel->attachment->getName();
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

					if (IOHelper::folderExists(craft()->path->getLogPath()))
					{
						// Grab it all.
						$templateFolderContents = IOHelper::getFolderContents(craft()->path->getSiteTemplatesPath());

						foreach ($templateFolderContents as $file)
						{
							// Make sure it's a file.
							if (IOHelper::fileExists($file))
							{
								$templateFolderName = IOHelper::getFolderName(craft()->path->getSiteTemplatesPath(), false);
								$siteTemplatePath = craft()->path->getSiteTemplatesPath();
								$tempPath = substr($siteTemplatePath, 0, (strlen($siteTemplatePath) - strlen($templateFolderName)) - 1);
								Zip::add($zipFile, $file, $tempPath);
							}
						}
					}
				}

				if ($zipFile)
				{
					$requestParams['File1_sFilename'] = 'SupportAttachment-'.IOHelper::cleanFilename(craft()->getSiteName()).'.zip';
					$requestParams['File1_sFileMimeType'] = 'application/zip';
					$requestParams['File1_bFileBody'] = base64_encode(IOHelper::getFileContents($zipFile));

					// Bump the default timeout because of the attachment.
					$hsParams['callTimeout'] = 120;
				}
			}
			catch(\Exception $e)
			{
				Craft::log('Tried to attach debug logs to a support request and something went horribly wrong: '.$e->getMessage(), LogLevel::Warning);

				// There was a problem zipping, so reset the params and just send the email without the attachment.
				$requestParams = $requestParamDefaults;
			}

			require_once craft()->path->getLibPath().'HelpSpotAPI.php';
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
				$errors = array('Support' => $hsErrors);
			}
		}
		else
		{
			$errors = $getHelpModel->getErrors();
		}

		$this->renderTemplate('_components/widgets/GetHelp/response',
			array(
				'success' => $success,
				'errors' => JsonHelper::encode($errors),
				'widgetId' => $widgetId
			)
		);
	}

	// Private Methods
	// =========================================================================

	/**
	 * @return string
	 */
	private function _createZip()
	{
		$zipFile = craft()->path->getTempPath().StringHelper::UUID().'.zip';
		IOHelper::createFile($zipFile);

		return $zipFile;
	}
}
