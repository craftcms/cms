<?php
namespace Blocks;

/**
 * @todo Authenticate requests to this.
 */
class UpdateController extends BaseController
{
	private $_blocksUpdateInfo;

	/**
	 * @param $h
	 * @return mixed
	 */
	public function actionGetUpdateInfo($h)
	{
		$returnUpdateInfo = array();
		$blocksUpdateInfo = Blocks::app()->update->updateInfo;
		if ($blocksUpdateInfo == null)
		{
			echo Json::encode(array('error' => 'There was a problem getting the latest update information.', 'fatal' => true));
			return;
		}

		$this->_blocksUpdateInfo = $blocksUpdateInfo;

		switch ($h)
		{
			case 'all':
			{
				// Blocks first.
				$returnUpdateInfo[] = array('handle' => 'Blocks', 'name' => 'Blocks', 'version' => $this->_blocksUpdateInfo->latestVersion.'.'.$this->_blocksUpdateInfo->latestBuild);

				// Plugins
				foreach ($this->_blocksUpdateInfo->plugins as $plugin)
				{
					if ($plugin->status == PluginVersionUpdateStatus::UpdateAvailable && count($plugin->newerReleases) > 0)
						$returnUpdateInfo[] = array('handle' => $plugin->class, 'name' => $plugin->displayName, 'version' => $plugin->latestVersion);
				}

				break;
			}

			case 'Blocks':
			{
			$returnUpdateInfo[] = array('handle' => 'Blocks', 'name' => 'Blocks', 'version' => $this->_blocksUpdateInfo->latestVersion.'.'.$this->_blocksUpdateInfo->latestBuild);
				break;
			}

			// we assume it's a plugin handle.
			default:
			{
				if ($this->_blocksUpdateInfo->plugins !== null && count($this->_blocksUpdateInfo->plugins) > 0)
				{
					if (isset($this->_blocksUpdateInfo->plugins[$h]) && $this->_blocksUpdateInfo->plugins[$h]->status == PluginVersionUpdateStatus::UpdateAvailable && count($this->_blocksUpdateInfo->plugins[$h]->newerReleases) > 0)
						$returnUpdateInfo[] = array('handle' => $this->_blocksUpdateInfo->plugins[$h]->handle, 'name' => $this->_blocksUpdateInfo->plugins[$h]->displayName, 'version' => $this->_blocksUpdateInfo->plugins[$h]->latestVersion);
					else
					{
						echo Json::encode(array('error' => 'Could not find any update information for the plugin with handle: '.$h.'.', 'fatal' => true));
						return;
					}
				}
				else
				{
					echo Json::encode(array('error' => 'Could not find any update information for the plugin with handle: '.$h.'.', 'fatal' => true));
					return;
				}
			}
		}

		echo Json::encode(array('updateInfo' => $returnUpdateInfo));
		return;
	}

	/**
	 * @param $h
	 * @return mixed
	 */
	public function actionUpdate($h)
	{
		switch ($h)
		{
			case 'Blocks':
			{
				try
				{
					if (Blocks::app()->update->doCoreUpdate())
						echo Json::encode(array('success' => true));
				}
				catch (Exception $ex)
				{
					echo Json::encode(array('error' => $ex->getMessage(), 'fatal' => true));
				}

				return;
			}

			// plugin handle
			default:
			{
				try
				{
					if (Blocks::app()->update->doPluginUpdate($h))
						echo Json::encode(array('success' => true));
				}
				catch (Exception $ex)
				{
					echo Json::encode(array('error' => $ex->getMessage(), 'fatal' => false));
				}

				return;
			}
		}
	}
}
