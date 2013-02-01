<?php
namespace Blocks;

/**
 *
 */
class UpdateController extends BaseController
{
	protected $allowAnonymous = array('actionManualUpdate', 'actionPrepare', 'actionBackupDatabase', 'actionUpdateDatabase', 'actionCleanUp');

	// -------------------------------------------
	//  Auto Updates
	// -------------------------------------------

	/**
	 * Returns the available updates
	 */
	public function actionGetAvailableUpdates()
	{
		blx()->userSession->requirePermission('autoUpdateBlocks');

		$updates = blx()->updates->getUpdates(true);
		$this->returnJson($updates);
	}

	/**
	 * Returns the update info JSON.
	 */
	public function actionGetUpdates()
	{
		blx()->userSession->requirePermission('autoUpdateBlocks');

		$this->requireAjaxRequest();

		$handle = blx()->request->getRequiredPost('handle');

		$return = array();
		$updateInfo = blx()->updates->getUpdates();

		if (!$updateInfo)
		{
			$this->returnErrorJson(Blocks::t('There was a problem getting the latest update information.'));
		}

		try
		{
			switch ($handle)
			{
				case 'all':
				{
					// Blocks first.
					$return[] = array('handle' => 'Blocks', 'name' => 'Blocks', 'version' => $updateInfo->blocks->latestVersion.'.'.$updateInfo->blocks->latestBuild, 'critical' => $updateInfo->blocks->criticalUpdateAvailable, 'releaseDate' => $updateInfo->blocks->latestDate->getTimestamp());

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

				case 'blocks':
				{
					$return[] = array('handle' => 'Blocks', 'name' => 'Blocks', 'version' => $updateInfo->blocks->latestVersion.'.'.$updateInfo->blocks->latestBuild, 'critical' => $updateInfo->blocks->criticalUpdateAvailable, 'releaseDate' => $updateInfo->blocks->latestDate->getTimestamp());
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
							$this->returnErrorJson(Blocks::t("Could not find any update information for the plugin with handle “{handle}”.", array('handle' => $handle)));
						}
					}
					else
					{
						$this->returnErrorJson(Blocks::t("Could not find any update information for the plugin with handle “{handle}”.", array('handle' => $handle)));
					}
				}
			}

			$r = array('success' => true, 'updateInfo' => $return);
			$this->returnJson($r);
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
	 * Index
	 */
	public function actionManualUpdate()
	{
		$this->renderTemplate('_special/dbupdate');
	}

	/**
	 *
	 */
	public function actionPrepare()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$data = blx()->request->getRequiredPost('data');

		$manual = false;
		if (!$this->_isManualUpdate($data))
		{
			// If it's not a manual update, make sure they have auto-update permissions.
			blx()->userSession->requirePermission('autoUpdateBlocks');
		}
		else
		{
			$manual = true;
		}

		$return = blx()->updates->prepareUpdate($manual, $data['handle']);

		if (!$return['success'])
		{
			$this->returnJson(array('error' => $return['message']));
		}

		if ($manual)
		{
			$this->returnJson(array('success' => true, 'nextStatus' => Blocks::t('Backing Up Database…'), 'nextAction' => 'update/backupDatabase', 'data' => $data));
		}
		else
		{
			$this->returnJson(array('success' => true, 'nextStatus' => Blocks::t('Downloading Update…'), 'nextAction' => 'update/processDownload'));
		}

	}

	/**
	 *
	 */
	public function actionProcessDownload()
	{
		// This method should never be called in a manual update.
		blx()->userSession->requirePermission('autoUpdateBlocks');

		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$return = blx()->updates->processUpdateDownload();
		if (!$return['success'])
		{
			$this->returnJson(array('error' => $return['message']));
		}

		unset($return['success']);

		$this->returnJson(array('success' => true, 'nextStatus' => Blocks::t('Backing Up Files…'), 'nextAction' => 'update/backupFiles', 'data' => $return));
	}

	/**
	 *
	 */
	public function actionBackupFiles()
	{
		// This method should never be called in a manual update.
		blx()->userSession->requirePermission('autoUpdateBlocks');

		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$data = blx()->request->getRequiredPost('data');

		$return = blx()->updates->backupFiles($data['uid']);
		if (!$return['success'])
		{
			$this->returnJson(array('error' => $return['message']));
		}

		$this->returnJson(array('success' => true, 'nextStatus' => Blocks::t('Updating Files…'), 'nextAction' => 'update/updateFiles', 'data' => $data));
	}

	/**
	 *
	 */
	public function actionUpdateFiles()
	{
		// This method should never be called in a manual update.
		blx()->userSession->requirePermission('autoUpdateBlocks');

		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$data = blx()->request->getRequiredPost('data');

		$return = blx()->updates->updateFiles($data['uid']);
		if (!$return['success'])
		{
			$this->returnJson(array('error' => $return['message']));
		}

		$this->returnJson(array('success' => true, 'nextStatus' => Blocks::t('Backing Up Database…'), 'nextAction' => 'update/backupDatabase', 'data' => $data));
	}

	/**
	 *
	 */
	public function actionBackupDatabase()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$data = blx()->request->getRequiredPost('data');

		if ($this->_isManualUpdate($data))
		{
			$uid = false;
		}
		else
		{
			// If it's not a manual update, make sure they have auto-update permissions.
			blx()->userSession->requirePermission('autoUpdateBlocks');

			$uid = $data['uid'];
		}

		if (blx()->config->get('backupDbOnUpdate'))
		{
			$return = blx()->updates->backupDatabase($uid);
			if (!$return['success'])
			{
				$this->returnJson(array('error' => $return['message']));
			}

			if (isset($return['dbBackupPath']))
			{
				$data['dbBackupPath'] = $return['dbBackupPath'];
			}
		}

		$this->returnJson(array('success' => true, 'nextStatus' => Blocks::t('Updating Database…'), 'nextAction' => 'update/updateDatabase', 'data' => $data));
	}

	/**
	 *
	 */
	public function actionUpdateDatabase()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$data = blx()->request->getRequiredPost('data');

		if ($this->_isManualUpdate($data))
		{
			$uid = false;
		}
		else
		{
			// If it's not a manual update, make sure they have auto-update permissions.
			blx()->userSession->requirePermission('autoUpdateBlocks');

			$uid = $data['uid'];
		}

		if (isset($data['dbBackupPath']))
		{
			$return = blx()->updates->updateDatabase($uid, $data['handle'], $data['dbBackupPath']);
		}
		else
		{
			$return = blx()->updates->updateDatabase($uid, $data['handle']);
		}

		if (!$return['success'])
		{
			$this->returnJson(array('error' => $return['message']));
		}

		$this->returnJson(array('success' => true, 'nextStatus' => Blocks::t('Cleaning Up…'), 'nextAction' => 'update/cleanUp', 'data' => $data));
	}

	/**
	 *
	 */
	public function actionCleanUp()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$data = blx()->request->getRequiredPost('data');

		if ($this->_isManualUpdate($data))
		{
			$uid = false;
		}
		else
		{
			// If it's not a manual update, make sure they have auto-update permissions.
			blx()->userSession->requirePermission('autoUpdateBlocks');

			$uid = $data['uid'];
		}

		$return = blx()->updates->updateCleanUp($uid, $data['handle']);
		if (!$return['success'])
		{
			$this->returnJson(array('error' => $return['message']));
		}

		$this->returnJson(array('success' => true, 'finished' => true));
	}

	/**
	 *
	 */
	public function actionRollback()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$data = blx()->request->getRequiredPost('data');

		if ($this->_isManualUpdate($data))
		{
			$uid = false;
		}
		else
		{
			// If it's not a manual update, make sure they have auto-update permissions.
			blx()->userSession->requirePermission('autoUpdateBlocks');

			$uid = $data['uid'];
		}

		if (isset($data['dbBackupPath']))
		{
			$return = blx()->updates->rollbackUpdate($uid, $data['dbBackupPath']);
		}
		else
		{
			$return = blx()->updates->rollbackUpdate($uid);
		}

		if (!$return['success'])
		{
			$this->returnJson(array('error' => $return['message']));
		}

		$this->returnJson(array('success' => true, 'finished' => true));
	}

	/**
	 * @param $data
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
}
