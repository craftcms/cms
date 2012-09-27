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
		$nav[] = array('handle' => 'dashboard', 'name' => Blocks::t('Dashboard'), 'icon' => 'D');
		$nav[] = array('handle' => 'content', 'name' => Blocks::t('Content'), 'icon' => 'C');
		$nav[] = array('handle' => 'assets', 'name' => Blocks::t('Assets'), 'icon' => 'A');

		if (Blocks::hasPackage(PackageType::Users))
		{
			$nav[] = array('handle' => 'users', 'name' => Blocks::t('Users'), 'icon' => 'U');
		}

		$nav[] = array('handle' => 'plugins', 'name' => Blocks::t('Plugins'), 'icon' => 'P');
		$nav[] = array('handle' => 'updates', 'name' => Blocks::t('Updates'), 'icon' => 'V', 'badge' => '1');
		$nav[] = array('handle' => 'settings', 'name' => Blocks::t('Settings'), 'icon' => 'S');

		return $nav;
	}
}
