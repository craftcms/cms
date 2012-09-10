<?php
namespace Blocks;

/**
 *
 */
class UpdatesWidget extends BaseWidget
{
	/**
	 * Returns the type of widget this is.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Blocks::t('Updates');
	}

	/**
	 * Gets the widget's body HTML.
	 *
	 * @return string
	 */
	public function getBodyHtml()
	{
		return TemplateHelper::render('_components/widgets/UpdatesWidget/body', array(
			'updates' => $this->_getUpdates()
		));
	}

	/**
	 * Gets the available updates.
	 *
	 * @access private
	 * @return array
	 */
	private function _getUpdates()
	{
		$updates = array();

		$updateInfo = blx()->updates->getUpdateInfo();

		// Blocks first
		if ($updateInfo->blocks->versionUpdateStatus == VersionUpdateStatus::UpdateAvailable)
		{
			$updates[] = array(
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
					$updates[] = array(
						'name'    => $plugin->displayName,
						'handle'  => $plugin->class,
						'version' => $plugin->latestVersion,
						'date'    => new DateTime('@'.$plugin->latestDate)
					);
				}
			}
		}

		return $updates;
	}
}
