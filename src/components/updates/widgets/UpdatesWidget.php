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
		return blx()->templates->render('_components/widgets/Updates/body', array(
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

		$updateModel = blx()->updates->getUpdateModel();

		// Blocks first
		if ($updateModel->blocks->versionUpdateStatus == VersionUpdateStatus::UpdateAvailable)
		{
			$updates[] = array(
				'name'     => 'Blocks',
				'handle'   => 'Blocks',
				'version'  => $updateModel->blocks->latestVersion.' Build '.$updateModel->blocks->latestBuild,
				'date'     => new DateTime('@'.$updateModel->blocks->latestDate),
				'critical' => $updateModel->blocks->criticalUpdateAvailable
			);
		}

		// Plugins next
		if ($updateModel->plugins !== null && count($updateModel->plugins) > 0)
		{
			foreach ($updateModel->plugins as $plugin)
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
