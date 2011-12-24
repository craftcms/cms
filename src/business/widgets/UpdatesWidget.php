<?php

class UpdatesWidget extends Widget
{
	public $title = 'Updates Available';
	public $className = 'updates';

	public function displayBody()
	{
		if (!Blocks::app()->update->isUpdateInfoCached())
			return false;

		$updateInfo = Blocks::app()->update->updateInfo;
		$updates = '';

		// Blocks first
		if ($updateInfo->versionUpdateStatus == BlocksVersionUpdateStatus::UpdateAvailable)
		{
			$updates .= '<tr>
							<td>Blocks '.$updateInfo->latestVersion.'.'.$updateInfo->latestBuild.'</td>'.'
							<td>'.BlocksHtml::link('Notes', array('settings/updates#Blocks')).'</td>
							<td><form method="post" action="'.Blocks::app()->urlManager->getBaseUrl().'/update?h=Blocks"><input id="update" class="btn" type="submit" value="Update"></form></td>
						</tr>';
		}

		// Plugins next
		if ($updateInfo->plugins !== null && count($blocksUpdateInfo->plugins) > 0)
		{
			foreach ($updateInfo->plugins as $plugin)
			{
				if ($plugin->status == PluginVersionUpdateStatus::UpdateAvailable)
				{
					$updates .= '<tr>
									<td>'.$plugin->displayName.' '.$plugin->latestVersion.'</td>
									<td>'.BlocksHtml::link('Notes', array('settings/updates#'.$plugin->handle)).'</td>
									<td><form method="post" action="'.Blocks::app()->urlManager->getBaseUrl().'/update?h='.$plugin->handle.'"><input id="update" class="btn" type="submit" value="Update"></form></td>
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
