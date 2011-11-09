<?php

class CPTag extends Tag
{
	private $defaultSections = array(
		'account' => 'Account',
		'dashboard' => 'Dashboard',
		'content' => 'Content',
		'assets' => 'Assets',
		'settings' => 'Settings',
		'guide' => 'User Guide',
	);

	public function sections()
	{
		$sections = $this->defaultSections;
		// scan through plugins dir for plugins that have a registerSection method and merge with $sections.
		return new CPSectionsTag($sections);
	}

	public function baseUrl()
	{
		$baseUrl = Blocks::app()->request->getSiteInfo()->url;
		return new StringTag($baseUrl);
	}
}
