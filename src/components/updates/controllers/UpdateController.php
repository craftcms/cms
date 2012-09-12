<?php
namespace Blocks;

/**
 *
 */
class UpdateController extends BaseController
{
	// -------------------------------------------
	//  Auto Updates
	// -------------------------------------------

	/**
	 * Returns the update info JSON.
	 *
	 * @param string $h The handle of which update to retrieve info for.
	 */
	public function actionGetUpdateInfo($h)
	{
		$this->requireLogin();
		$this->requireAjaxRequest();

		$return = array();
		$updateInfo = blx()->updates->getUpdateInfo();

		if (!$updateInfo)
			$this->returnErrorJson(Blocks::t('There was a problem getting the latest update information.'));

		try
		{
			switch ($h)
			{
				case 'all':
				{
					// Blocks first.
					$return[] = array('handle' => 'Blocks', 'name' => 'Blocks', 'version' => $updateInfo->blocks->latestVersion.'.'.$updateInfo->blocks->latestBuild);

					// Plugins
					if ($updateInfo->plugins !== null)
					{
						foreach ($updateInfo->plugins as $plugin)
						{
							if ($plugin->status == PluginVersionUpdateStatus::UpdateAvailable && count($plugin->releases) > 0)
								$return[] = array('handle' => $plugin->class, 'name' => $plugin->displayName, 'version' => $plugin->latestVersion);
						}
					}

					break;
				}

				case 'Blocks':
				{
					$return[] = array('handle' => 'Blocks', 'name' => 'Blocks', 'version' => $updateInfo->blocks->latestVersion.'.'.$updateInfo->blocks->latestBuild);
					break;
				}

				// We assume it's a plugin handle.
				default:
				{
					if (!empty($updateInfo->plugins))
					{
						if (isset($updateInfo->plugins[$h]) && $updateInfo->plugins[$h]->status == PluginVersionUpdateStatus::UpdateAvailable && count($updateInfo->plugins[$h]->releases) > 0)
							$return[] = array('handle' => $updateInfo->plugins[$h]->handle, 'name' => $updateInfo->plugins[$h]->displayName, 'version' => $updateInfo->plugins[$h]->latestVersion);
						else
							$this->returnErrorJson(Blocks::t("Could not find any update information for the plugin with handle “{handle}”.", array('handle' => $h)));
					}
					else
						$this->returnErrorJson(Blocks::t("Could not find any update information for the plugin with handle “{handle}”.", array('handle' => $h)));
				}
			}

			$r = array('updateInfo' => $return);
			$this->returnJson($r);
		}
		catch (\Exception $e)
		{
			$this->returnErrorJson($e->getMessage());
		}
	}

	/**
	 * Runs an update.
	 *
	 * @param string $h The handle of what to update.
	 */
	public function actionRunAutoUpdate($h)
	{
		$this->requireLogin();
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		try
		{
			switch ($h)
			{
				case 'Blocks':
				{
					blx()->updates->doAppUpdate();
					break;
				}

				// Plugin handle
				default:
				{
					blx()->updates->doPluginUpdate($h);
				}
			}

			$this->returnJson(array('success' => true));
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
	public function actionRunManualUpdate()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		try
		{
			// Take the system offline.
			blx()->updates->turnSystemOffBeforeUpdate();

			// run migrations to top
			if (blx()->migrations->runToTop())
			{
				// update db with version info.
				if (blx()->updates->setNewBlocksInfo(Blocks::getVersion(), Blocks::getBuild(), Blocks::getReleaseDate()))
				{
					// flush update cache.
					blx()->updates->flushUpdateInfoFromCache();
					blx()->user->setNotice(Blocks::t('Database successfully updated.'));

					// Bring the system back online.
					blx()->updates->turnSystemOnAfterUpdate();

					$this->returnJson(array('success' => true));
				}
			}

			$this->returnJson(array('error' => Blocks::t('There was a problem updating the database.')));
		}
		catch (\Exception $e)
		{
			Blocks::log($e->getMessage(), \CLogger::LEVEL_ERROR);
			$this->returnJson(array('error' => Blocks::t('There was a problem updating the database.')));
		}
	}
}
