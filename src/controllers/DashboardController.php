<?php
namespace Craft;

/**
 *
 */
class DashboardController extends BaseController
{
	/**
	 * Saves a widget.
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
				$item['date'] = $item['date']->w3cDate();
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
		$getHelpModel->message = craft()->request->getPost('message');
		$getHelpModel->attachDebugFiles = (bool)craft()->request->getPost('attachDebugFiles');
		$getHelpModel->attachment = \CUploadedFile::getInstanceByName('attachAdditionalFile');

		if ($getHelpModel->validate())
		{
			$user = craft()->userSession->getUser();

			$requestParamDefaults = array(
				'sFirstName' => $user->getFriendlyName(),
				'sLastName' => ($user->lastName ? $user->lastName : 'Doe'),
				'sEmail' => $getHelpModel->fromEmail,
				'tNote' => $getHelpModel->message,
			);

			$requestParams = $requestParamDefaults;

			$hsParams = array(
				'helpSpotApiURL' => 'https://support.pixelandtonic.com/api/index.php'
			);

			try
			{
				if ($getHelpModel->attachDebugFiles)
				{
					$zipFile = craft()->path->getTempPath().StringHelper::UUID().'.zip';
					IOHelper::createFile($zipFile);

					if (IOHelper::folderExists(craft()->path->getLogPath()))
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

					if (IOHelper::folderExists(craft()->path->getDbBackupPath()))
					{
						// Make a fresh database backup of the current schema/data.
						craft()->db->backup();

						$contents = IOHelper::getFolderContents(craft()->path->getDbBackupPath());
						rsort($contents);

						// Only grab the most recent 3 sorted by timestamp.
						for ($counter = 0; $counter <= 2; $counter++)
						{
							if (isset($contents[$counter]))
							{
								Zip::add($zipFile, $contents[$counter], craft()->path->getStoragePath());
							}
						}
					}
				}

				if ($getHelpModel->attachment)
				{
					// If we don't have a zip file yet, create one now.
					if (!$zipFile)
					{
						$zipFile = craft()->path->getTempPath().StringHelper::UUID().'.zip';
						IOHelper::createFile($zipFile);
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

				if ($zipFile)
				{
					$requestParams['File1_sFilename'] = 'SupportAttachment.zip';
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
}
