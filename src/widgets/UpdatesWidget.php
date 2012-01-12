<?php

/**
 *
 */
class UpdatesWidget extends Widget
{
	public $title = 'Updates Available';
	public $className = 'updates';

	/**
	 * @access public
	 *
	 * @return bool
	 */
	public function displayBody()
	{
		if (!Blocks::app()->update->isUpdateInfoCached())
			return false;

		$updateInfo = Blocks::app()->update->updateInfo;
		$updates = array();

		// Blocks first
		if ($updateInfo->versionUpdateStatus == BlocksVersionUpdateStatus::UpdateAvailable)
		{
			$updates[] = array(
				'name' => 'Blocks',
				'handle' => 'Blocks',
				'version' => $updateInfo->latestVersion.'.'.$updateInfo->latestBuild
			);
		}

		// Plugins next
		if ($updateInfo->plugins !== null && count($updateInfo->plugins) > 0)
		{
			foreach ($updateInfo->plugins as $plugin)
			{
				if ($plugin->status == PluginVersionUpdateStatus::UpdateAvailable)
				{
					$updates[] = array(
						'name' => $plugin->displayName,
						'handle' => $plugin->handle,
						'version' => $plugin->latestVersion
					);
				}
			}
		}

		if ($updates)
		{
			$tags = array(
				'updates' => $updates
			);

			return Blocks::app()->controller->loadTemplate('_widgets/UpdatesWidget/body', $tags, true);
		}

		return false;
	}
}
