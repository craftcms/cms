<?php
namespace Craft;

/**
 *
 */
class DashboardController extends BaseController
{
	/**
	 *
	 */
	public function actionGetAlerts()
	{
		$alerts = DashboardHelper::getAlerts(true);
		$r = array('alerts' => $alerts);
		$this->returnJson($r);
	}

	/**
	 * Saves a widget.
	 */
	public function actionSaveUserWidget()
	{
		$this->requirePostRequest();

		$widget = new WidgetModel();
		$widget->id = craft()->request->getPost('widgetId');
		$widget->type = craft()->request->getRequiredPost('type');

		$typeSettings = craft()->request->getPost('types');

		if (isset($typeSettings[$widget->type]))
		{
			$widget->settings = $typeSettings[$widget->type];
		}

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

		// Reload the original template
		$this->renderRequestedTemplate(array(
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
		$this->requireAjaxRequest();

		$message = craft()->request->getRequiredPost('message');

		$user = craft()->userSession->getUser();

		$requestParamDefaults = array(
			'sFirstName' => $user->getFriendlyName(),
			'sLastName' => ($user->lastName ? $user->lastName : 'Doe'),
			'sEmail' => $user->email,
			'tNote' => $message,
		);

		$requestParams = $requestParamDefaults;

		$hsParams = array(
			'helpSpotApiURL' => 'https://support.buildwithcraft.com/api/index.php'
		);

		$attachment = (bool)craft()->request->getPort('attachDebugFiles');

		try
		{
			if ($attachment)
			{
				$tempZipFile = craft()->path->getTempPath().StringHelper::UUID().'.zip';
				IOHelper::createFile($tempZipFile);

				if (IOHelper::folderExists(craft()->path->getLogPath()))
				{
					// Grab the latest log file.
					Zip::add($tempZipFile, craft()->path->getLogPath().'craft.log', craft()->path->getStoragePath());

					// Grab the most recent rolled-over log file, if one exists.
					if (IOHelper::fileExists(craft()->path->getLogPath().'craft.log.1'))
					{
						Zip::add($tempZipFile, craft()->path->getLogPath().'craft.log.1', craft()->path->getStoragePath());
					}

					// Grab the phperrors log file, if it exists.
					if (IOHelper::fileExists(craft()->path->getLogPath().'phperrors.log'))
					{
						Zip::add($tempZipFile, craft()->path->getLogPath().'phperrors.log', craft()->path->getRuntimePath());
					}
				}

				if (IOHelper::folderExists(craft()->path->getDbBackupPath()))
				{
					$contents = IOHelper::getFolderContents(craft()->path->getDbBackupPath());
					rsort($contents);

					// Only grab the most recent 5 sorted by timestamp.
					for ($counter = 0; $counter <= 4; $counter++)
					{
						Zip::add($tempZipFile, $contents[$counter], craft()->path->getStoragePath());
					}
				}

				$requestParams['File1_sFilename'] = 'SupportAttachment.zip';
				$requestParams['File1_sFileMimeType'] = 'application/zip';
				$requestParams['File1_bFileBody'] = base64_encode(IOHelper::getFileContents($tempZipFile));

				// Bump the default timeout because of the attachment.
				$hsParams['callTimeout'] = 60;
			}
		}
		catch(\Exception $e)
		{
			Craft::log('Tried to attach debug logs to a support request and something went horribly wrong: '.$e->getMessage(), \CLogger::LEVEL_WARNING);

			// There was a problem zipping, so reset the params and just send the email without the attachment.
			$requestParams = $requestParamDefaults;
		}

		require_once craft()->path->getLibPath().'HelpSpotAPI.php';
		$hsapi = new \HelpSpotAPI($hsParams);

		$result = $hsapi->requestCreate($requestParams);

		if ($result)
		{
			if ($attachment)
			{
				if (IOHelper::fileExists($tempZipFile))
				{
					IOHelper::deleteFile($tempZipFile);
				}
			}

			$this->returnJson(array('success' => true));
		}
		else
		{
			$this->returnErrorJson($hsapi->errors);
		}
	}

	/**
	 * Returns the update widget HTML.
	 */
	public function actionCheckForUpdates()
	{
		craft()->updates->getUpdates();

		$this->renderTemplate('_components/widgets/Updates/body', array(
			'total' => craft()->updates->getTotalAvailableUpdates()
		));
	}
}
