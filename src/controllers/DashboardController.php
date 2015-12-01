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
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.controllers
 * @since     1.0
 */
class DashboardController extends BaseController
{
	// Public Methods
	// =========================================================================

	/**
	 * Dashboard index.
	 *
	 * @param array $variables
	 *
	 * @return null
	 */
	public function actionIndex(array $variables = array())
	{
		$dashboardService = craft()->dashboard;
		$templatesService = craft()->templates;

		$oldNamespace = $templatesService->getNamespace();

		// Assemble the list of available widget types
		$widgetTypes = $dashboardService->getAllWidgetTypes();
		$widgetTypeInfo = array();
		$templatesService->setNamespace('__NAMESPACE__');

		foreach ($widgetTypes as $widgetType)
		{
			$templatesService->startJsBuffer();
			$settingsHtml = $templatesService->namespaceInputs($widgetType->getSettingsHtml());
			$settingsJs = $templatesService->clearJsBuffer(false);

			$handle = $widgetType->getClassHandle();

			$widgetTypeInfo[$handle] = array(
				'iconSvg' => $this->_getWidgetIconSvg($widgetType),
				'name' => $widgetType->getName(),
				'maxColspan' => $widgetType->getMaxColspan(),
				'settingsHtml' => (string) $settingsHtml,
				'settingsJs' => (string) $settingsJs,
				'selectable' => true,
			);
		}

		$templatesService->setNamespace(null);

		// Assemble the list of existing widgets
		$variables['widgets'] = array();
		$widgets = $dashboardService->getUserWidgets();
		$allWidgetJs = '';

		foreach ($widgets as $widget)
		{
			$templatesService->startJsBuffer();
			$info = $this->_getWidgetInfo($widget);
			$widgetJs = $templatesService->clearJsBuffer(false);

			if ($info === false)
			{
				continue;
			}

			// If this widget type didn't come back in our getAllWidgetTypes() call, add it now
			if (!isset($widgetTypeInfo[$info['type']]))
			{
				$widgetType = $dashboardService->populateWidgetType($widget);
				$widgetTypeInfo[$info['type']] = array(
					'iconSvg' => $this->_getWidgetIconSvg($widgetType),
					'name' => $widgetType->getName(),
					'maxColspan' => $widgetType->getMaxColspan(),
					'selectable' => false,
				);
			}

			$variables['widgets'][] = $info;

			$allWidgetJs .= 'new Craft.Widget("#widget'.$widget->id.'", '.
				JsonHelper::encode($info['settingsHtml']).', '.
				'function(){'.$info['settingsJs'].'}'.
				");\n";

			if ($widgetJs)
			{
				// Allow any widget JS to execute *after* we've created the Craft.Widget instance
				$allWidgetJs .= $widgetJs."\n";
			}
		}

		// Include all the JS and CSS stuff
		$templatesService->includeCssResource('css/dashboard.css');
		$templatesService->includeJsResource('js/Dashboard.js');
		$templatesService->includeJs('window.dashboard = new Craft.Dashboard('.JsonHelper::encode($widgetTypeInfo).');');
		$templatesService->includeJs($allWidgetJs);
		$templatesService->includeTranslations(
			'1 column',
			'{num} columns',
			'{type} Settings',
			'Widget saved.',
			'Couldn’t save widget.',
			'You don’t have any widgets yet.'
		);

		$variables['widgetTypes'] = $widgetTypeInfo;
		$this->renderTemplate('dashboard/_index', $variables);
	}

	/**
	 * Creates a new widget.
	 *
	 * @return void
	 */
	public function actionCreateWidget()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$request = craft()->request;

		$type = $request->getRequiredPost('type');
		$settingsNamespace = $request->getPost('settingsNamespace');

		$widget = new WidgetModel();
		$widget->type = $type;

		if ($settingsNamespace)
		{
			$widget->settings = craft()->request->getPost($settingsNamespace);
		}

		$this->_saveAndReturnWidget($widget);
	}

	/**
	 * Saves a widget’s settings.
	 *
	 * @return void
	 *
	 * @throws HttpException
	 */
	public function actionSaveWidgetSettings()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$request = craft()->request;
		$dashboardService = craft()->dashboard;

		$widgetId = $request->getRequiredPost('widgetId');
		$widget = $dashboardService->getUserWidgetById($widgetId);

		if (!$widget)
		{
			throw new HttpException(400);
		}

		$widget->settings = $request->getPost('widget'.$widget->id.'-settings');

		$this->_saveAndReturnWidget($widget);
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
	 * Changes the colspan of a widget.
	 *
	 * @return null
	 */
	public function actionChangeWidgetColspan()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$widgetId = craft()->request->getRequiredPost('id');
		$colspan = craft()->request->getRequiredPost('colspan');

		craft()->dashboard->changeWidgetColspan($widgetId, $colspan);

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

		$namespace = craft()->request->getPost('namespace');
		$namespace = $namespace ? $namespace.'.' : '';

		$getHelpModel = new GetHelpModel();
		$getHelpModel->fromEmail = craft()->request->getPost($namespace.'fromEmail');
		$getHelpModel->message = trim(craft()->request->getPost($namespace.'message'));
		$getHelpModel->attachLogs = (bool) craft()->request->getPost($namespace.'attachLogs');
		$getHelpModel->attachDbBackup = (bool) craft()->request->getPost($namespace.'attachDbBackup');
		$getHelpModel->attachTemplates = (bool)craft()->request->getPost($namespace.'attachTemplates');
		$getHelpModel->attachment = UploadedFile::getInstanceByName($namespace.'attachAdditionalFile');

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

				// Grab the license.key file.
				if (IOHelper::fileExists(craft()->path->getLicenseKeyPath()))
				{
					$requestParams['File2_sFilename'] = 'license.key';
					$requestParams['File2_sFileMimeType'] = 'text/plain';
					$requestParams['File2_bFileBody'] = base64_encode(IOHelper::getFileContents(craft()->path->getLicenseKeyPath()));
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
	 * Returns the info about a widget required to display its body and settings in the Dashboard.
	 *
	 * @param WidgetModel $widget
	 *
	 * @return array|false
	 */
	private function _getWidgetInfo(WidgetModel $widget)
	{
		$dashboardService = craft()->dashboard;
		$widgetType = $dashboardService->populateWidgetType($widget);

		if (!$widgetType)
		{
			return false;
		}

		$templatesService = craft()->templates;
		$namespace = $templatesService->getNamespace();

		// Get the body HTML
		$widgetBodyHtml = $widgetType->getBodyHtml();

		if (!$widgetBodyHtml)
		{
			return false;
		}

		// Get the settings HTML + JS
		$templatesService->setNamespace('widget'.$widget->id.'-settings');
		$templatesService->startJsBuffer();
		$settingsHtml = $templatesService->namespaceInputs($widgetType->getSettingsHtml());
		$settingsJs = $templatesService->clearJsBuffer(false);

		// Get the colspan (limited to the widget type's max allowed colspan)
		$colspan = ($widget->colspan ?: 1);

		if (($maxColspan = $widgetType->getMaxColspan()) && $colspan > $maxColspan)
		{
			$colspan = $maxColspan;
		}

		$templatesService->setNamespace($namespace);

		return array(
			'id' => $widget->id,
			'type' => $widgetType->getClassHandle(),
			'colspan' => $colspan,
			'title' => $widgetType->getTitle(),
			'name' => $widgetType->getName(),
			'bodyHtml' => $widgetBodyHtml,
			'settingsHtml' => (string) $settingsHtml,
			'settingsJs' => (string) $settingsJs,
		);
	}

	/**
	 * Returns a widget type’s SVG icon.
	 *
	 * @param IWidget $widgetType
	 *
	 * @return string
	 */
	private function _getWidgetIconSvg(IWidget $widgetType)
	{
		$iconPath = $widgetType->getIconPath();

		if ($iconPath && IOHelper::fileExists($iconPath) && FileHelper::getMimeType($iconPath) == 'image/svg+xml')
		{
			return IOHelper::getFileContents($iconPath);
		}

		return craft()->templates->render('_includes/defaulticon.svg', array(
			'label' => $widgetType->getName()
		));
	}

	/**
	 * Attempts to save a widget and responds with JSON.
	 *
	 * @param WidgetModel $widget
	 *
	 * @return void
	 */
	private function _saveAndReturnWidget(WidgetModel $widget)
	{
		if (craft()->dashboard->saveUserWidget($widget))
		{
			$info = $this->_getWidgetInfo($widget);
			$templatesService = craft()->templates;

			$this->returnJson(array(
				'success' => true,
				'info' => $info,
				'headHtml' => $templatesService->getHeadHtml(),
				'footHtml' => $templatesService->getFootHtml(),
			));
		}
		else
		{
			$errors = $widget->getAllErrors();

			foreach ($widget->getSettingErrors() as $attribute => $attributeErrors)
			{
				$errors = array_merge($errors, $attributeErrors);
			}

			$this->returnJson(array(
				'errors' => $errors
			));
		}
	}

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
