<?php
namespace Blocks;

/**
 * CP functions
 */
class CpVariable extends Component
{
	/**
	 * Get the sections of the CP.
	 * @return array
	 */
	public function sections()
	{
		$sections[] = array('handle' => 'dashboard', 'name' => 'Dashboard', 'icon' => 'D');
		$sections[] = array('handle' => 'content', 'name' => 'Content', 'icon' => 'C');
		//$sections[] = array('handle' => 'assets', 'name' => 'Assets', 'icon' => 'A');
		$sections[] = array('handle' => 'users', 'name' => 'Users', 'icon' => 'U');

		if (b()->users->current && b()->users->current->admin)
		{
			$sections[] = array('handle' => 'settings', 'name' => 'Settings', 'icon' => 'S');
			$sections[] = array('handle' => 'updates', 'name' => 'Updates', 'icon' => 'V');
		}

		//$sections[] = array('handle' => 'guide', 'name' => 'Guide', 'icon' => 'G');

		return $sections;
	}
}
