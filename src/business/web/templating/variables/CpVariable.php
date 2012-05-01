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
		$sections[] = array('handle' => 'dashboard', 'name' => 'Dashboard');
		$sections[] = array('handle' => 'content', 'name' => 'Content');
		//$sections[] = array('handle' => 'assets', 'name' => 'Assets');
		$sections[] = array('handle' => 'users', 'name' => 'Users');

		if (b()->users->current && b()->users->current->admin)
			$sections[] = array('handle' => 'settings', 'name' => 'Settings');

		//$sections[] = array('handle' => 'guide', 'name' => 'Guide');

		return $sections;
	}
}
