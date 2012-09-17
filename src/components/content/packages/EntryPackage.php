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
	public $publishDate;
	/* BLOCKSPRO ONLY */
	public $expiryDate;
	/* end BLOCKSPRO ONLY */
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

	public function status()
	{
		$currentTime = DateTimeHelper::currentTime();
		/* BLOCKS ONLY */
		if ($this->publishDate && $this->publishDate <= $currentTime)
		/* end BLOCKS ONLY */
		/* BLOCKSPRO ONLY */
		if ($this->publishDate && $this->publishDate <= $currentTime && (!$this->expiryDate || $this->expiryDate > $currentTime))
		/* end BLOCKSPRO ONLY */
		{
			return 'live';
		}
		else if ($this->publishDate > $currentTime)
		{
			return 'pending';
		}
		/* BLOCKSPRO ONLY */
		else if ($this->expiryDate && $this->expiryDate <= $currentTime)
		{
			return 'expired';
		}
		/* end BLOCKSPRO ONLY */
		else
		{
			return 'unpublished';
		}
	}
}
