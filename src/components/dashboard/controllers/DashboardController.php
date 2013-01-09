<?php
namespace Blocks;

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
		$widget->id = blx()->request->getPost('widgetId');
		$widget->type = blx()->request->getRequiredPost('type');

		$typeSettings = blx()->request->getPost('types');

		if (isset($typeSettings[$widget->type]))
		{
			$widget->settings = $typeSettings[$widget->type];
		}

		// Did it save?
		if (blx()->dashboard->saveUserWidget($widget))
		{
			blx()->userSession->setNotice(Blocks::t('Widget saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			blx()->userSession->setError(Blocks::t('Couldn’t save widget.'));
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

		$widgetId = JsonHelper::decode(blx()->request->getRequiredPost('id'));
		blx()->dashboard->deleteUserWidgetById($widgetId);

		$this->returnJson(array('success' => true));
	}

	/**
	 * Reorders widgets.
	 */
	public function actionReorderUserWidgets()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$widgetIds = JsonHelper::decode(blx()->request->getRequiredPost('ids'));
		blx()->dashboard->reorderUserWidgets($widgetIds);

		$this->returnJson(array('success' => true));
	}

	/**
	 * Returns the items for the Feed widget.
	 */
	public function actionGetFeedItems()
	{
		$this->requireAjaxRequest();

		$url = blx()->request->getRequiredParam('url');
		$limit = blx()->request->getParam('limit');

		$items = blx()->dashboard->getFeedItems($url, $limit);
		$this->returnJson(array('items' => $items));
	}

	/**
	 * Creates a new support ticket for the GetHelp widget.
	 */
	public function actionSendSupportRequest()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$message = blx()->request->getRequiredPost('message');

		$user = blx()->userSession->getUser();

		$requestParamDefaults = array(
			'sFirstName' => $user->getFriendlyName(),
			'sLastName' => ($user->lastName ? $user->lastName : 'Doe'),
			'sEmail' => $user->email,
			'tNote' => $message,
		);

		$requestParams = $requestParamDefaults;

		$hsParams = array(
			'helpSpotApiURL' => 'https://support.blockscms.com/api/index.php'
		);

		$attachment = (bool)blx()->request->getPort('attachDebugFiles');

		try
		{
			if ($attachment)
			{
				$tempZipFile = blx()->path->getTempPath().StringHelper::UUID().'.zip';
				IOHelper::createFile($tempZipFile);

				if (IOHelper::folderExists(blx()->path->getLogPath()))
				{
					// Grab the latest log file.
					Zip::add($tempZipFile, blx()->path->getLogPath().'blocks.log', blx()->path->getStoragePath());

					// Grab the most recent rolled-over log file, if one exists.
					if (IOHelper::fileExists(blx()->path->getLogPath().'blocks.log.1'))
					{
						Zip::add($tempZipFile, blx()->path->getLogPath().'blocks1.log.1', blx()->path->getStoragePath());
					}

					// Grab the phperrors log file, if it exists.
					if (IOHelper::fileExists(blx()->path->getLogPath().'phperrors.log'))
					{
						Zip::add($tempZipFile, blx()->path->getLogPath().'phperrors.log', blx()->path->getRuntimePath());
					}
				}

				if (IOHelper::folderExists(blx()->path->getDbBackupPath()))
				{
					$contents = IOHelper::getFolderContents(blx()->path->getDbBackupPath());
					rsort($contents);

					// Only grab the most recent 5 sorted by timestamp.
					for ($counter = 0; $counter <= 4; $counter++)
					{
						Zip::add($tempZipFile, $contents[$counter], blx()->path->getStoragePath());
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
			Blocks::log('Tried to attach debug logs to a support request and something went horribly wrong: '.$e->getMessage(), \CLogger::LEVEL_WARNING);

			// There was a problem zipping, so reset the params and just send the email without the attachment.
			$requestParams = $requestParamDefaults;
		}

		require_once blx()->path->getLibPath().'HelpSpotAPI.php';
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
		blx()->updates->getUpdates();

		$this->renderTemplate('_components/widgets/Updates/body', array(
			'total' => blx()->updates->getTotalNumberOfAvailableUpdates()
		));
	}
}
