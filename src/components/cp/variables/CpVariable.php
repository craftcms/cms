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
		$nav['dashboard'] = array('name' => Blocks::t('Dashboard'), 'icon' => 'D');
		$nav['content'] = array('name' => Blocks::t('Content'), 'icon' => 'C');
		$nav['assets'] = array('name' => Blocks::t('Assets'), 'icon' => 'A');

		if (Blocks::hasPackage(BlocksPackage::Users))
		{
			$nav['users'] = array('name' => Blocks::t('Users'), 'icon' => 'U');
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

		$nav['updates'] = array('name' => Blocks::t('Updates'), 'icon' => 'V', 'badge' => '1');
		$nav['settings'] = array('name' => Blocks::t('Settings'), 'icon' => 'S');

		return $nav;
	}
}
