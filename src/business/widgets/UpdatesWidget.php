<?php

class UpdatesWidget extends Widget
{
	public $title = 'Updates Available';
	public $className = 'updates';
	private $_blocksUpdateInfo;

	public function init()
	{
		$this->_blocksUpdateInfo = Blocks::app()->request->getBlocksUpdateInfo();
		$this->_process();
	}

	private function _process()
	{
		$this->body =
					'<a class="btn dark update-all" href=""><span class="label">Update all</span></a>
					<table>
						<tbody>';

		if ($this->_blocksUpdateInfo['blocksVersionUpdateStatus'] == BlocksVersionUpdateStatus::UpdateAvailable)
		{
			$this->body .= '<tr>
								<td>Blocks '.$this->_blocksUpdateInfo['blocksLatestVersionNo'].'.'.$this->_blocksUpdateInfo['blocksLatestBuildNo'].'</td>'.'
								<td><a href="">Notes</a></td>
								<td><a class="btn" href=""><span class="label">Update</span></a></td>
							</tr>';
		}

		if (isset($this->_blocksUpdateInfo['pluginNamesAndVersions']) && $this->_blocksUpdateInfo['pluginNamesAndVersions'] !== null && count($this->_blocksUpdateInfo['pluginNamesAndVersions']) > 0)
		{
			foreach ($this->_blocksUpdateInfo['pluginNamesAndVersions'] as $pluginInfo)
			{
				if ($pluginInfo['status'] == PluginVersionUpdateStatus::UpdateAvailable)
				{
					$this->body .= '<tr>
										<td>'.$pluginInfo['displayName'].' '.$pluginInfo['latestVersion'].'</td>
										<td><a href="">Notes</a></td>
										<td><a class="btn" href=""><span class="label">Update</span></a></td>
									</tr>';
				}
			}
		}

		$this->body .= '</tbody></table>';
	}
}
