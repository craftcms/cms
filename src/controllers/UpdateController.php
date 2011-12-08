<?php

class UpdateController extends BaseController
{
	// authenticate requests to this

	private $_blocksUpdateInfo;

	public function actionGetUpdateInfo($h)
	{
		$returnUpdateInfo = array();
		$blocksUpdateInfo = Blocks::app()->request->blocksUpdateInfo;
		if ($blocksUpdateInfo == null)
		{
			echo CJSON::encode(array('error' => 'There was a problem getting the latest update information.', 'fatal' => true));
			return;
		}

		$this->_blocksUpdateInfo = $blocksUpdateInfo;

		switch ($h)
		{
			case 'all':
			{
				// Blocks first.
				$returnUpdateInfo[] = array('handle' => 'Blocks', 'name' => 'Blocks', 'version' => $this->_blocksUpdateInfo['blocksLatestVersionNo'].'.'.$this->_blocksUpdateInfo['blocksLatestBuildNo']);

				// Plugins
				foreach ($this->_blocksUpdateInfo['pluginNamesAndVersions'] as $pluginInfo)
				{
					if ($pluginInfo['status'] == PluginVersionUpdateStatus::UpdateAvailable && count($pluginInfo['newerReleases']) > 0)
						$returnUpdateInfo[] = array('handle' => $pluginInfo['handle'], 'name' => $pluginInfo['displayName'], 'version' => $pluginInfo['latestVersion']);
				}

				break;
			}

			case 'Blocks':
			{
				$returnUpdateInfo[] = array('handle' => 'Blocks', 'name' => 'Blocks', 'version' => $this->_blocksUpdateInfo['blocksLatestVersionNo'].'.'.$this->_blocksUpdateInfo['blocksLatestBuildNo']);
				break;
			}

			// we assume it's a plugin handle.
			default:
			{
				if (isset($this->_blocksUpdateInfo['pluginNamesAndVersions']) && count($this->_blocksUpdateInfo['pluginNamesAndVersions']) > 0)
				{
					if (isset($this->_blocksUpdateInfo['pluginNamesAndVersions'][$h]) && $this->_blocksUpdateInfo['pluginNamesAndVersions'][$h]['status'] == PluginVersionUpdateStatus::UpdateAvailable && count($this->_blocksUpdateInfo['pluginNamesAndVersions'][$h]['newerReleases']) > 0)
						$returnUpdateInfo[] = array('handle' => $this->_blocksUpdateInfo['pluginNamesAndVersions'][$h]['handle'], 'name' => $this->_blocksUpdateInfo['pluginNamesAndVersions'][$h]['displayName'], 'version' => $this->_blocksUpdateInfo['pluginNamesAndVersions'][$h]['latestVersion']);
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

		echo CJSON::encode(array('updateInfo' => $returnUpdateInfo));
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
					$coreUpdater = new CoreUpdater();
					if ($coreUpdater->start())
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
					$pluginUpdater = new PluginUpdater();
					if ($pluginUpdater->start())
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
