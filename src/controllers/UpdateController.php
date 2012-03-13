<?php
namespace Blocks;

/**
 * @todo Authenticate requests to this.
 */
class UpdateController extends Controller
{
	private $_blocksUpdateInfo;

	/**
	 * All update actions require the user to be logged in
	 */
	public function init()
	{
		$this->requireLogin();
	}

	/**
	 * @param $h
	 * @return mixed
	 */
	public function actionGetUpdateInfo($h)
	{
		$returnUpdateInfo = array();
		$blocksUpdateInfo = b()->updates->updateInfo;
		if ($blocksUpdateInfo == null)
		{
			$r = array('error' => 'There was a problem getting the latest update information.', 'fatal' => true);
			$this->returnJson($r);
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

		$r = array('updateInfo' => $returnUpdateInfo);
		$this->returnJson($r);
	}

	/**
	 * @param $h
	 * @return mixed
	 */
	public function actionUpdate($h)
	{
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

			// plugin handle
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
