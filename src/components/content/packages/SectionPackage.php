<?php
namespace Blocks;

/**
 * Section package class
 *
 * Used for transporting section data throughout the system.
 */
class SectionPackage extends BasePackage
{
	public $name;
	public $handle;
	public $hasUrls = false;
	public $urlFormat;
	public $template;

	/**
	 * Saves the section.
	 *
	 * @return bool
	 */
	public function save()
	{
		return blx()->content->saveSection($this);
	}
}
