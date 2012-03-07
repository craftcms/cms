<?php
namespace Blocks;

/**
 *
 */
class UpdatesWidget extends Widget
{
	public $widgetName = 'Updates';
	public $title = 'Updates Available';

	public $updates = array();

	protected $bodyTemplate = '_widgets/UpdatesWidget/body';

	/**
	 * @return bool
	 */
	public function displayBody()
	{
		if (!b()->updates->isUpdateInfoCached())
			return false;

		$updateInfo = b()->updates->updateInfo;

		// Blocks first
		if ($updateInfo->versionUpdateStatus == VersionUpdateStatus::UpdateAvailable)
		{
			$this->updates[] = array(
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
					$this->updates[] = array(
						'name' => $plugin->displayName,
						'handle' => $plugin->class,
						'version' => $plugin->latestVersion
					);
				}
			}
		}

		if ($this->updates)
		{
			return parent::displayBody();
		}

		return false;
	}
}
