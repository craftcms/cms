<?php
namespace Blocks;

/**
 *
 */
class UpdateController extends Controller
{
	/**
	 * All update actions require the user to be logged in
	 */
	public function init()
	{
		$this->requireLogin();
	}

	/**
	 * Returns the update info JSON.
	 * @param string $h The handle of which update to retrieve info for.
	 */
	public function actionGetUpdateInfo($h)
	{
		$this->requireAjaxRequest();

		$return = array();
		$updateInfo = b()->updates->updateInfo;
		if ($updateInfo == null)
		{
			$r = array('error' => 'There was a problem getting the latest update information.', 'fatal' => true);
			$this->returnJson($r);
		}

		switch ($h)
		{
			case 'all':
			{
				// Blocks first.
				$return[] = array('handle' => 'Blocks', 'name' => 'Blocks', 'version' => $updateInfo->latestVersion.'.'.$updateInfo->latestBuild);

				// Plugins
				if ($updateInfo->plugins !== null)
				{
					foreach ($updateInfo->plugins as $plugin)
					{
						if ($plugin->status == PluginVersionUpdateStatus::UpdateAvailable && count($plugin->newerReleases) > 0)
							$return[] = array('handle' => $plugin->class, 'name' => $plugin->displayName, 'version' => $plugin->latestVersion);
					}
				}

				break;
			}

			case 'Blocks':
			{
				$return[] = array('handle' => 'Blocks', 'name' => 'Blocks', 'version' => $updateInfo->latestVersion.'.'.$updateInfo->latestBuild);
				break;
			}

			// We assume it's a plugin handle.
			default:
			{
				if ($updateInfo->plugins !== null && count($updateInfo->plugins) > 0)
				{
					if (isset($updateInfo->plugins[$h]) && $updateInfo->plugins[$h]->status == PluginVersionUpdateStatus::UpdateAvailable && count($updateInfo->plugins[$h]->newerReleases) > 0)
						$return[] = array('handle' => $updateInfo->plugins[$h]->handle, 'name' => $updateInfo->plugins[$h]->displayName, 'version' => $updateInfo->plugins[$h]->latestVersion);
					else
					{
						$r = array('error' => 'Could not find any update information for the plugin with handle: '.$h.'.', 'fatal' => true);
						$this->returnJson($r);
					}
				}
				else
				{
					$r = array('error' => 'Could not find any update information for the plugin with handle: '.$h.'.', 'fatal' => true);
					$this->returnJson($r);
				}
			}
		}

		$r = array('updateInfo' => $return);
		$this->returnJson($r);
	}

	/**
	 * Runs an update.
	 * @param string $h The handle of what to update.
	 */
	public function actionUpdate($h)
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$r = array();

		switch ($h)
		{
			case 'Blocks':
			{
				try
				{
					if (b()->updates->doCoreUpdate())
						$r = array('success' => true);
				}
				catch (Exception $ex)
				{
					$r = array('error' => $ex->getMessage(), 'fatal' => true);
				}

				break;
			}

			// Plugin handle
			default:
			{
				try
				{
					if (b()->updates->doPluginUpdate($h))
						$r = array('success' => true);
				}
				catch (Exception $ex)
				{
					$r = array('error' => $ex->getMessage(), 'fatal' => false);
				}
			}
		}

		$this->returnJson($r);
	}
}
