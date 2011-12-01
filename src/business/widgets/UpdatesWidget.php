<?php

class UpdatesWidget extends Widget
{
	public $title = 'Updates Available';
	public $className = 'updates';

	public function body()
	{
		$blocksUpdateInfo = Blocks::app()->request->getBlocksUpdateInfo();

		if ($blocksUpdateInfo['blocksLicenseStatus'] == LicenseKeyStatus::MissingKey)
			return false;

		$updates = '';

		if ($blocksUpdateInfo['blocksVersionUpdateStatus'] == BlocksVersionUpdateStatus::UpdateAvailable)
		{
			$updates .= '<tr>
								<td>Blocks '.$blocksUpdateInfo['blocksLatestVersionNo'].'.'.$blocksUpdateInfo['blocksLatestBuildNo'].'</td>'.'
								<td>'.BlocksHtml::link('Notes', array('settings/updates#Blocks')).'</td>
								<td><a class="btn" href=""><span class="label">Update</span></a></td>
							</tr>';
		}

		if (isset($blocksUpdateInfo['pluginNamesAndVersions']) && $blocksUpdateInfo['pluginNamesAndVersions'] !== null && count($blocksUpdateInfo['pluginNamesAndVersions']) > 0)
		{
			foreach ($blocksUpdateInfo['pluginNamesAndVersions'] as $pluginInfo)
			{
				if ($pluginInfo['status'] == PluginVersionUpdateStatus::UpdateAvailable)
				{
					$updates .= '<tr>
										<td>'.$pluginInfo['displayName'].' '.$pluginInfo['latestVersion'].'</td>
										<td>'.BlocksHtml::link('Notes', array('settings/updates#'.$pluginInfo['handle'])).'</td>
										<td><a class="btn" href=""><span class="label">Update</span></a></td>
									</tr>';
				}
			}
		}

		if ($updates)
		{
			return '<a class="btn dark update-all" href=""><span class="label">Update all</span></a>
				<table>
					<tbody>' .
						$updates .
					'</tbody>
				</table>';
		}

		return false;
	}
}
