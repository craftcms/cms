<?php

class UpdatesWidget extends Widget
{
	public $title = 'Updates Available';
	public $className = 'updates';

	public function displayBody()
	{
		$blocksUpdateInfo = Blocks::app()->update->blocksUpdateInfo();

		// don't show the widget if the update info isn't already cached or there's a missing license key
		if ($blocksUpdateInfo === false || $blocksUpdateInfo['blocksLicenseStatus'] == LicenseKeyStatus::MissingKey)
			return false;

		$updates = '';

		// Blocks first
		if ($blocksUpdateInfo['blocksVersionUpdateStatus'] == BlocksVersionUpdateStatus::UpdateAvailable)
		{
			$updates .= '<tr>
							<td>Blocks '.$blocksUpdateInfo['blocksLatestVersionNo'].'.'.$blocksUpdateInfo['blocksLatestBuildNo'].'</td>'.'
							<td>'.BlocksHtml::link('Notes', array('settings/updates#Blocks')).'</td>
							<td><form method="post" action="'.Blocks::app()->urlManager->getBaseUrl().'/update?h=Blocks"><input id="update" class="btn" type="submit" value="Update"></form></td>
						</tr>';
		}

		// Plugins next
		if (isset($blocksUpdateInfo['pluginNamesAndVersions']) && $blocksUpdateInfo['pluginNamesAndVersions'] !== null && count($blocksUpdateInfo['pluginNamesAndVersions']) > 0)
		{
			foreach ($blocksUpdateInfo['pluginNamesAndVersions'] as $pluginInfo)
			{
				if ($pluginInfo['status'] == PluginVersionUpdateStatus::UpdateAvailable)
				{
					$updates .= '<tr>
									<td>'.$pluginInfo['displayName'].' '.$pluginInfo['latestVersion'].'</td>
									<td>'.BlocksHtml::link('Notes', array('settings/updates#'.$pluginInfo['handle'])).'</td>
									<td><form method="post" action="'.Blocks::app()->urlManager->getBaseUrl().'/update?h='.$pluginInfo['handle'].'"><input id="update" class="btn" type="submit" value="Update"></form></td>
								</tr>';
				}
			}
		}

		if ($updates)
		{
			return '<form method="post" action="'.Blocks::app()->urlManager->getBaseUrl().'/update?h=all"><input id="update-all" class="btn dark update-all" type="submit" value="Update All"></form>
				<table>
					<tbody>' .
						$updates .
					'</tbody>
				</table>';
		}

		return false;
	}
}
