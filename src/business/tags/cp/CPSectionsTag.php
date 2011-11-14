<?php

class CPSectionsTag extends Tag
{
	private static $defaultSections = array(
		'dashboard' => 'Dashboard',
		'content' => 'Content',
		'assets' => 'Assets',
		'settings' => 'Settings',
		'guide' => 'User Guide',
	);

	public function __toArray()
	{
		$tags = array();

		foreach (self::$defaultSections as $handle => $name)
		{
			$tags[] = new CPSectiontag($handle, $name);
		}

		return $tags;
	}
}
