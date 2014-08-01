<?php
namespace Craft;

/**
 * Class UpdateController
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.controllers
 * @since     1.0
 */
class UpdateController extends BaseController
{
	/**
	 * If set to false, you are required to be logged in to execute any of the given controller's actions.
	 * If set to true, anonymous access is allowed for all of the given controller's actions.
	 * If the value is an array of action names, then you must be logged in for any action method except for the ones in the array list.
	 * If you have a controller that where the majority of action methods will be anonymous, but you only want require login on a few, it's best to use craft()->userSession->requireLogin() in the individual methods.
	 *
	 * @var bool
	 */
	protected $allowAnonymous = array('actionManualUpdate', 'actionPrepare', 'actionBackupDatabase', 'actionUpdateDatabase', 'actionCleanUp', 'actionRollback');

	// -------------------------------------------
	//  Auto Updates
	// -------------------------------------------

	/**
	 * Returns the available updates.
	 *
	 * @return void
	 */
	public function actionGetAvailableUpdates()
	{
		craft()->userSession->requirePermission('performUpdates');

		try
		{
			$updates = craft()->updates->getUpdates(true);
		}
		catch (EtException $e)
		{
			if ($e->getCode() == 10001)
			{
				$this->returnErrorJson($e->getMessage());
			}
		}

		if ($updates)
		{
			$this->returnJson($updates);
		}
		else
		{
			$this->returnErrorJson(Craft::t('Could not fetch available updates at this time.'));
		}
	}

	/**
	 * Returns the update info JSON.
	 *
	 * @return void
	 */
	public function actionGetUpdates()
	{
		craft()->userSession->requirePermission('performUpdates');

		$this->requireAjaxRequest();

		$handle = craft()->request->getRequiredPost('handle');

		$return = array();
		$updateInfo = craft()->updates->getUpdates();

		if (!$updateInfo)
		{
			$this->returnErrorJson(Craft::t('There was a problem getting the latest update information.'));
		}

		try
		{
			switch ($handle)
			{
				case 'all':
				{
					// Craft first.
					$return[] = array('handle' => 'Craft', 'name' => 'Craft', 'version' => $updateInfo->app->latestVersion.'.'.$updateInfo->app->latestBuild, 'critical' => $updateInfo->app->criticalUpdateAvailable, 'releaseDate' => $updateInfo->app->latestDate->getTimestamp());

					// Plugins
					if ($updateInfo->plugins !== null)
					{
						foreach ($updateInfo->plugins as $plugin)
						{
							if ($plugin->status == PluginVersionUpdateStatus::UpdateAvailable && count($plugin->releases) > 0)
							{
								$return[] = array('handle' => $plugin->class, 'name' => $plugin->displayName, 'version' => $plugin->latestVersion, 'critical' => $plugin->criticalUpdateAvailable, 'releaseDate' => $plugin->latestDate->getTimestamp());
							}
						}
					}

					break;
				}

				case 'craft':
				{
					$return[] = array('handle' => 'Craft', 'name' => 'Craft', 'version' => $updateInfo->app->latestVersion.'.'.$updateInfo->app->latestBuild, 'critical' => $updateInfo->app->criticalUpdateAvailable, 'releaseDate' => $updateInfo->app->latestDate->getTimestamp());
					break;
				}

				// We assume it's a plugin handle.
				default:
				{
					if (!empty($updateInfo->plugins))
					{
						if (isset($updateInfo->plugins[$handle]) && $updateInfo->plugins[$handle]->status == PluginVersionUpdateStatus::UpdateAvailable && count($updateInfo->plugins[$handle]->releases) > 0)
						{
							$return[] = array('handle' => $updateInfo->plugins[$handle]->handle, 'name' => $updateInfo->plugins[$handle]->displayName, 'version' => $updateInfo->plugins[$handle]->latestVersion, 'critical' => $updateInfo->plugins[$handle]->criticalUpdateAvailable, 'releaseDate' => $updateInfo->plugins[$handle]->latestDate->getTimestamp());
						}
						else
						{
							$this->returnErrorJson(Craft::t("Could not find any update information for the plugin with handle “{handle}”.", array('handle' => $handle)));
						}
					}
					else
					{
						$this->returnErrorJson(Craft::t("Could not find any update information for the plugin with handle “{handle}”.", array('handle' => $handle)));
					}
				}
			}

			$this->returnJson(array('success' => true, 'updateInfo' => $return));
		}
		catch (\Exception $e)
		{
			$this->returnErrorJson($e->getMessage());
		}
	}

	// -------------------------------------------
	//  Manual Updates
	// -------------------------------------------

	/**
	 * @return void
	 */
	public function actionPrepare()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$data = craft()->request->getRequiredPost('data');

		$manual = false;
		if (!$this->_isManualUpdate($data))
		{
			// If it's not a manual update, make sure they have auto-update permissions.
			craft()->userSession->requirePermission('performUpdates');

			if (!craft()->config->get('allowAutoUpdates'))
			{
				$this->returnJson(array('alive' => true, 'errorDetails' => Craft::t('Auto-updating is disabled on this system.'), 'finished' => true));
			}
		}
		else
		{
			$manual = true;
		}

		$return = craft()->updates->prepareUpdate($manual, $data['handle']);

		if (!$return['success'])
		{
			$this->returnJson(array('alive' => true, 'errorDetails' => $return['message'], 'finished' => true));
		}

		if ($manual)
		{
			$this->returnJson(array('alive' => true, 'nextStatus' => Craft::t('Backing-up database…'), 'nextAction' => 'update/backupDatabase', 'data' => $data));
		}
		else
		{
			$data['md5'] = $return['md5'];
			$this->returnJson(array('alive' => true, 'nextStatus' => Craft::t('Downloading update…'), 'nextAction' => 'update/processDownload', 'data' => $data));
		}

	}

	/**
	 * @return void
	 */
	public function actionProcessDownload()
	{
		// This method should never be called in a manual update.
		craft()->userSession->requirePermission('performUpdates');

		$this->requirePostRequest();
		$this->requireAjaxRequest();

		if (!craft()->config->get('allowAutoUpdates'))
		{
			$this->returnJson(array('alive' => true, 'errorDetails' => Craft::t('Auto-updating is disabled on this system.'), 'finished' => true));
		}

		$data = craft()->request->getRequiredPost('data');

		$return = craft()->updates->processUpdateDownload($data['md5']);
		if (!$return['success'])
		{
			$this->returnJson(array('alive' => true, 'errorDetails' => $return['message'], 'finished' => true));
		}

		unset($return['success']);

		$this->returnJson(array('alive' => true, 'nextStatus' => Craft::t('Backing-up files…'), 'nextAction' => 'update/backupFiles', 'data' => $return));
	}

	/**
	 * @return void
	 */
	public function actionBackupFiles()
	{
		// This method should never be called in a manual update.
		craft()->userSession->requirePermission('performUpdates');

		$this->requirePostRequest();
		$this->requireAjaxRequest();

		if (!craft()->config->get('allowAutoUpdates'))
		{
			$this->returnJson(array('alive' => true, 'errorDetails' => Craft::t('Auto-updating is disabled on this system.'), 'finished' => true));
		}

		$data = craft()->request->getRequiredPost('data');

		$return = craft()->updates->backupFiles($data['uid']);
		if (!$return['success'])
		{
			$this->returnJson(array('alive' => true, 'errorDetails' => $return['message'], 'finished' => true));
		}

		$this->returnJson(array('alive' => true, 'nextStatus' => Craft::t('Updating files…'), 'nextAction' => 'update/updateFiles', 'data' => $data));
	}

	/**
	 * @return void
	 */
	public function actionUpdateFiles()
	{
		// This method should never be called in a manual update.
		craft()->userSession->requirePermission('performUpdates');

		$this->requirePostRequest();
		$this->requireAjaxRequest();

		if (!craft()->config->get('allowAutoUpdates'))
		{
			$this->returnJson(array('alive' => true, 'errorDetails' => Craft::t('Auto-updating is disabled on this system.'), 'finished' => true));
		}

		$data = craft()->request->getRequiredPost('data');

		$return = craft()->updates->updateFiles($data['uid']);
		if (!$return['success'])
		{
			$this->returnJson(array('alive' => true, 'errorDetails' => $return['message'], 'nextStatus' => Craft::t('An error was encountered. Rolling back…'), 'nextAction' => 'update/rollback'));
		}

		$this->returnJson(array('alive' => true, 'nextStatus' => Craft::t('Backing-up database…'), 'nextAction' => 'update/backupDatabase', 'data' => $data));
	}

	/**
	 * @return void
	 */
	public function actionBackupDatabase()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$data = craft()->request->getRequiredPost('data');

		if (!$this->_isManualUpdate($data))
		{
			// If it's not a manual update, make sure they have auto-update permissions.
			craft()->userSession->requirePermission('performUpdates');

			if (!craft()->config->get('allowAutoUpdates'))
			{
				$this->returnJson(array('alive' => true, 'errorDetails' => Craft::t('Auto-updating is disabled on this system.'), 'finished' => true));
			}
		}

		$handle = $this->_getFixedHandle($data);

		if (craft()->config->get('backupDbOnUpdate'))
		{
			$plugin = craft()->plugins->getPlugin($handle);

			// If this a plugin, make sure it actually has new migrations before backing up the database.
			if ($handle == 'craft' || ($plugin && craft()->migrations->getNewMigrations($plugin)))
			{
				$return = craft()->updates->backupDatabase();

				if (!$return['success'])
				{
					$this->returnJson(array('alive' => true, 'errorDetails' => $return['message'], 'nextStatus' => Craft::t('An error was encountered. Rolling back…'), 'nextAction' => 'update/rollback'));
				}

				if (isset($return['dbBackupPath']))
				{
					$data['dbBackupPath'] = $return['dbBackupPath'];
				}
			}
		}

		$this->returnJson(array('alive' => true, 'nextStatus' => Craft::t('Updating database…'), 'nextAction' => 'update/updateDatabase', 'data' => $data));
	}

	/**
	 * @return void
	 */
	public function actionUpdateDatabase()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$data = craft()->request->getRequiredPost('data');

		if (!$this->_isManualUpdate($data))
		{
			// If it's not a manual update, make sure they have auto-update permissions.
			craft()->userSession->requirePermission('performUpdates');

			if (!craft()->config->get('allowAutoUpdates'))
			{
				$this->returnJson(array('alive' => true, 'errorDetails' => Craft::t('Auto-updating is disabled on this system.'), 'finished' => true));
			}
		}

		$handle = $this->_getFixedHandle($data);

		if (isset($data['dbBackupPath']))
		{
			$return = craft()->updates->updateDatabase($handle);
		}
		else
		{
			$return = craft()->updates->updateDatabase($handle);
		}

		if (!$return['success'])
		{
			$this->returnJson(array('alive' => true, 'errorDetails' => $return['message'], 'nextStatus' => Craft::t('An error was encountered. Rolling back…'), 'nextAction' => 'update/rollback'));
		}

		$this->returnJson(array('alive' => true, 'nextStatus' => Craft::t('Cleaning up…'), 'nextAction' => 'update/cleanUp', 'data' => $data));
	}

	/**
	 * Performs maintenance and clean up tasks after an update.
	 *
	 * @return void
	 */
	public function actionCleanUp()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$data = craft()->request->getRequiredPost('data');

		if ($this->_isManualUpdate($data))
		{
			$uid = false;
		}
		else
		{
			// If it's not a manual update, make sure they have auto-update permissions.
			craft()->userSession->requirePermission('performUpdates');

			if (!craft()->config->get('allowAutoUpdates'))
			{
				$this->returnJson(array('alive' => true, 'errorDetails' => Craft::t('Auto-updating is disabled on this system.'), 'finished' => true));
			}

			$uid = $data['uid'];
		}

		$handle = $this->_getFixedHandle($data);

		$oldVersion = false;

		// Grab the old version from the manifest data before we nuke it.
		$manifestData = UpdateHelper::getManifestData(UpdateHelper::getUnzipFolderFromUID($uid));

		if ($manifestData)
		{
			$oldVersion = UpdateHelper::getLocalVersionFromManifest($manifestData);
		}

		craft()->updates->updateCleanUp($uid, $handle);

		if ($oldVersion && version_compare($oldVersion, craft()->getVersion(), '<'))
		{
			$returnUrl = UrlHelper::getUrl('whats-new');
		}
		else
		{
			$returnUrl = craft()->userSession->getReturnUrl();
		}

		$this->returnJson(array('alive' => true, 'finished' => true, 'returnUrl' => $returnUrl));
	}

	/**
	 * @throws Exception
	 * @return void
	 */
	public function actionRollback()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$data = craft()->request->getRequiredPost('data');

		if ($this->_isManualUpdate($data))
		{
			$uid = false;
		}
		else
		{
			// If it's not a manual update, make sure they have auto-update permissions.
			craft()->userSession->requirePermission('performUpdates');

			if (!craft()->config->get('allowAutoUpdates'))
			{
				$this->returnJson(array('alive' => true, 'errorDetails' => Craft::t('Auto-updating is disabled on this system.'), 'finished' => true));
			}

			$uid = $data['uid'];
		}

		if (isset($data['dbBackupPath']))
		{
			$return = craft()->updates->rollbackUpdate($uid, $data['dbBackupPath']);
		}
		else
		{
			$return = craft()->updates->rollbackUpdate($uid);
		}

		if (!$return['success'])
		{
			// Let the JS handle the exception response.
			throw new Exception($return['message']);
		}

		$this->returnJson(array('alive' => true, 'finished' => true, 'rollBack' => true));
	}

	/**
	 * @param $data
	 *
	 * @return bool
	 */
	private function _isManualUpdate($data)
	{
		if (isset($data['manualUpdate']) && $data['manualUpdate'] == 1)
		{
			return true;
		}

		return false;
	}

	/**
	 * @param $data
	 *
	 * @return string
	 */
	private function _getFixedHandle($data)
	{
		if (!isset($data['handle']))
		{
			return 'craft';
		}
		else
		{
			return $data['handle'];
		}
	}
}
