<?php
namespace Blocks;

/**
 *
 */
class CpService extends BaseComponent
{
	/**
	 * Get the sections of the CP
	 * @return array
	 */
	public function getSections()
	{
		return array(
			array('handle' => 'dashboard', 'name' => 'Dashboard'),
			array('handle' => 'content', 'name' => 'Content'),
			array('handle' => 'assets', 'name' => 'Assets'),
			array('handle' => 'users', 'name' => 'Users'),
			array('handle' => 'settings', 'name' => 'Settings'),
			array('handle' => 'guide', 'name' => 'Guide'),
		);
	}
}
