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
			echo CJSON::encode(array('error' => 'There was a problem getting the latest update information.'));
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
						echo CJSON::encode(array('error' => 'Could not find any update information for the plugin with handle: '.$h.'.'));
						return;
					}
				}
				else
				{
					echo CJSON::encode(array('error' => 'Could not find any update information for the plugin with handle: '.$h.'.'));
					return;
				}
			}
		}

		echo CJSON::encode(array('updateInfo' => $returnUpdateInfo));
		return;
	}

	public function actionUpdate($h)
	{
		echo CJSON::encode(array('success' => true));
		return;
	}

	private function _coreUpdate()
	{
		/*
		try
		{
			$coreUpdater = new CoreUpdater($this->_blocksUpdateInfo['blocksLatestVersionNo'], $this->_blocksUpdateInfo['blocksLatestBuildNo'], Blocks::getEdition());
			if ($coreUpdater->start())
				Blocks::app()->user->setFlash('notice', 'Update Successful!');

			$this->redirect('index');
		}
		catch (BlocksException $ex)
		{
			Blocks::app()->user->setFlash('error', $ex->getMessage());
			$this->redirect('index');
		}*/
	}

	private function _pluginUpdate()
	{

	}
}
