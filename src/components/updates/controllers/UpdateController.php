<?php
namespace Blocks;

/**
 *
 */
class UpdateController extends BaseController
{
	protected $allowAnonymous = array('actionManualUpdate', 'actionRunManualUpdate');

	// -------------------------------------------
	//  Auto Updates
	// -------------------------------------------

	/**
	 * Returns the available updates
	 */
	public function actionGetAvailableUpdates()
	{
		$updates = blx()->updates->getUpdates(true);
		$this->returnJson($updates);
	}

	/**
	 * Redirects to the Blocks download URL.
	 */
	public function actionDownloadBlocksUpdate()
	{
		$url = 'https://elliott.blockscms.com/actions/licenses/downloadBlocks?licenseKey='.Blocks::getLicenseKey();
		blx()->request->redirect($url);
	}

	/**
	 * Returns the update info JSON.
	 */
	public function actionGetUpdates()
	{
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

	/**
	 * Runs an update.
	 */
	public function actionRunAutoUpdate()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$handle = blx()->request->getRequiredPost('handle');

		try
		{
			switch ($handle)
			{
				case 'Blocks':
				{
					blx()->updates->doAppUpdate();
					break;
				}

				// Plugin handle
				default:
				{
					blx()->updates->doPluginUpdate($handle);
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
