<?php
namespace Blocks;

/**
 * Entry package class
 *
 * Used for transporting entry data throughout the system.
 */
class EntryPackage extends BasePackage
{
	/* BLOCKSPRO ONLY */
	public $authorId;
	public $sectionId;
	public $language;
	/* end BLOCKSPRO ONLY */
	public $title;
	public $slug;
	public $blocks;
	public $blockErrors;

	/**
	 * Saves the entry.
	 *
	 * @return bool
	 */
	public function save()
	{
		return blx()->content->saveEntry($this);
	}
}
