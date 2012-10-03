<?php
namespace Blocks;

/**
 * CP functions
 */
class CpVariable
{
	/**
	 * Get the sections of the CP.
	 *
	 * @return array
	 */
	public function nav()
	{
		$nav['dashboard'] = array('name' => Blocks::t('Dashboard'));
		$nav['content'] = array('name' => Blocks::t('Content'));
		$nav['assets'] = array('name' => Blocks::t('Assets'));

		if (Blocks::hasPackage(BlocksPackage::Users))
		{
			$nav['users'] = array('name' => Blocks::t('Users'));
		}

		// Add any Plugin nav items
		$plugins = blx()->plugins->getEnabledPlugins();

		foreach ($plugins as $plugin)
		{
			if ($plugin->hasCpSection)
			{
				$key = strtolower($plugin->getClassHandle());
				$nav[$key] = array('name' => $plugin->getName());
			}
		}

		$nav['updates'] = array('name' => Blocks::t('Updates'), 'badge' => '1');
		$nav['settings'] = array('name' => Blocks::t('Settings'));

		return $nav;
	}
}
