<?php

class UpdateController extends BaseController
{
	// authenticate requests to this

	private $_blocksUpdateData;

	public function actionGetUpdateInfo($h)
	{
		$returnUpdateData = array();
		$blocksUpdateData = Blocks::app()->update->blocksUpdateInfo();
		if ($blocksUpdateData == null)
		{
			echo CJSON::encode(array('error' => 'There was a problem getting the latest update information.', 'fatal' => true));
			return;
		}

		$this->_blocksUpdateData = $blocksUpdateData;

		switch ($h)
		{
			case 'all':
			{
				// Blocks first.
				$returnUpdateData[] = array('handle' => 'Blocks', 'name' => 'Blocks', 'version' => $this->_blocksUpdateData->latestVersion.'.'.$this->_blocksUpdateData->latestBuild);

				// Plugins
				foreach ($this->_blocksUpdateData->plugins as $plugin)
				{
					if ($plugin->status == PluginVersionUpdateStatus::UpdateAvailable && count($plugin->newerReleases) > 0)
						$returnUpdateData[] = array('handle' => $plugin->handle, 'name' => $plugin->displayName, 'version' => $plugin->latestVersion);
				}

				break;
			}

			case 'Blocks':
			{
				$returnUpdateData[] = array('handle' => 'Blocks', 'name' => 'Blocks', 'version' => $this->_blocksUpdateData->latestVersion.'.'.$this->_blocksUpdateData->latestBuild);
				break;
			}

			// we assume it's a plugin handle.
			default:
			{
				if ($this->_blocksUpdateData->plugins !== null && count($this->_blocksUpdateData->plugins) > 0)
				{
					if (isset($this->_blocksUpdateData->plugins[$h]) && $this->_blocksUpdateData->plugins[$h]->status == PluginVersionUpdateStatus::UpdateAvailable && count($this->_blocksUpdateData->plugins[$h]->newerReleases) > 0)
						$returnUpdateData[] = array('handle' => $this->_blocksUpdateData->plugins[$h]->handle, 'name' => $this->_blocksUpdateData->plugins[$h]->displayName, 'version' => $this->_blocksUpdateData->plugins[$h]->latestVersion);
					else
					{
						echo CJSON::encode(array('error' => 'Could not find any update information for the plugin with handle: '.$h.'.', 'fatal' => true));
						return;
					}
				}
				else
				{
					echo CJSON::encode(array('error' => 'Could not find any update information for the plugin with handle: '.$h.'.', 'fatal' => true));
					return;
				}
			}
		}

		echo CJSON::encode(array('updateInfo' => $returnUpdateData));
		return;
	}

	public function actionUpdate($h)
	{
		switch ($h)
		{
			case 'Blocks':
			{
				try
				{
					if (Blocks::app()->update->doCoreUpdate())
						echo CJSON::encode(array('success' => true));
				}
				catch (BlocksException $ex)
				{
					echo CJSON::encode(array('error' => $ex->getMessage(), 'fatal' => true));
				}

				return;
			}

			// plugin handle
			default:
			{
				try
				{
					if (Blocks::app()->update->doPluginUpdate($h))
						echo CJSON::encode(array('success' => true));
				}
				catch (BlocksException $ex)
				{
					echo CJSON::encode(array('error' => $ex->getMessage(), 'fatal' => false));
				}

				return;
			}
		}
	}
}
