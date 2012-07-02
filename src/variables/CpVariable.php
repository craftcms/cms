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
				array('handle' => 'dashboard', 'name' => 'Dashboard', 'icon' => 'D'),
			),
			array(
				array('handle' => 'content', 'name' => 'Content', 'icon' => 'C'),
				array('handle' => 'assets', 'name' => 'Assets', 'icon' => 'A'),
			),
			array(
				array('handle' => 'users', 'name' => 'Users', 'icon' => 'U'),
				array('handle' => 'plugins', 'name' => 'Plugins', 'icon' => 'P'),
				array('handle' => 'updates', 'name' => 'Updates', 'icon' => 'V', 'badge' => '1'),
				array('handle' => 'settings', 'name' => 'Settings', 'icon' => 'S'),
			)
		);
	}
}
