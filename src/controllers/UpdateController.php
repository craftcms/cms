<?php
namespace Blocks;

/**
 *
 */
class UpdateController extends BaseController
{
	/**
	 * All update actions require the user to be logged in.
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
		$updateInfo = blx()->updates->getUpdateInfo();

		if (!$updateInfo)
			$this->returnErrorJson('There was a problem getting the latest update information.');

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
							$this->returnErrorJson("Could not find any update information for the plugin with handle “{$h}”.");
					}
					else
						$this->returnErrorJson("Could not find any update information for the plugin with handle “{$h}”.");
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
	 * @param string $h The handle of what to update.
	 */
	public function actionUpdate($h)
	{
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
}
