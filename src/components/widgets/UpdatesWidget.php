<?php
namespace Blocks;

/**
 *
 */
class UpdatesWidget extends BaseWidget
{
	public $name = 'Updates';

	public $updates = array();

	protected $bodyTemplate = '_components/widgets/UpdatesWidget/body';

	/**
	 * Add a link to Updates.
	 *
	 * @return array
	 */
	public function getActionButtons()
	{
		return array(
			array(
				'label' => 'Updates',
				'url' => 'updates'
			)
		);
	}

	/**
	 * Gets the widget title.
	 *
	 * @return string
	 */
	public function getTitle()
	{
		return 'Updates';
	}

	/**
	 * Gets the widget body.
	 *
	 * @return string
	 */
	public function getBody()
	{
		$updateInfo = blx()->updates->getUpdateInfo();

		// Blocks first
		if ($updateInfo->blocks->versionUpdateStatus == VersionUpdateStatus::UpdateAvailable)
		{
			$this->updates[] = array(
				'name'     => 'Blocks',
				'handle'   => 'Blocks',
				'version'  => $updateInfo->blocks->latestVersion.' Build '.$updateInfo->blocks->latestBuild,
				'date'     => new DateTime('@'.$updateInfo->blocks->latestDate),
				'critical' => $updateInfo->blocks->criticalUpdateAvailable
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
						'name'    => $plugin->displayName,
						'handle'  => $plugin->class,
						'version' => $plugin->latestVersion,
						'date'    => new DateTime('@'.$plugin->latestDate)
					);
				}
			}
		}

		return parent::getBody();
	}
}
