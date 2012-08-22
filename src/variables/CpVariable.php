<?php
namespace Blocks;

/**
 * CP functions
 */
class CpVariable
{
	/**
	 * Get the sections of the CP.
	 * @return array
	 */
	public function nav()
	{
		return array(
			array(
				array('handle' => 'dashboard', 'name' => Blocks::t('Dashboard'), 'icon' => 'D'),
			),
			array(
				array('handle' => 'content', 'name' => Blocks::t('Content'), 'icon' => 'C'),
				array('handle' => 'assets', 'name' => Blocks::t('Assets'), 'icon' => 'A'),
			),
			array(
				array('handle' => 'plugins', 'name' => Blocks::t('Plugins'), 'icon' => 'P'),
				array('handle' => 'updates', 'name' => Blocks::t('Updates'), 'icon' => 'V', 'badge' => '1'),
				array('handle' => 'settings', 'name' => Blocks::t('Settings'), 'icon' => 'S'),
			)
		);
	}
}
