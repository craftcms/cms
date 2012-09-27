<?php
namespace Blocks;

/**
 * Entry package class
 *
 * Used for transporting entry data throughout the system.
 */
class EntryPackage extends BasePackage
{
	public $draftId;
	/* BLOCKSPRO ONLY */
	public $authorId;
	public $sectionId;
	public $language;
	/* end BLOCKSPRO ONLY */
	public $title;
	public $slug;
	public $postDate;
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

	/**
	 * Saves the entry draft.
	 *
	 * @return bool
	 */
	public function saveDraft()
	{
		return blx()->content->saveEntryDraft($this);
	}

	/**
	 * Returns the entries status.
	 */
	public function status()
	{
		$currentTime = DateTimeHelper::currentTime();
		$postDate = ($this->postDate ? $this->postDate->getTimestamp() : null);
		/* BLOCKSPRO ONLY */
		$expiryDate = ($this->expiryDate ? $this->expiryDate->getTimestamp() : null);
		/* end BLOCKSPRO ONLY */

		/* BLOCKS ONLY */
		if ($postDate && $postDate <= $currentTime)
		/* end BLOCKS ONLY */
		/* BLOCKSPRO ONLY */
		if ($postDate && $postDate <= $currentTime && (!$expiryDate || $expiryDate > $currentTime))
		/* end BLOCKSPRO ONLY */
		{
			return 'live';
		}
		else if ($postDate && $postDate > $currentTime)
		{
			return 'pending';
		}
		/* BLOCKSPRO ONLY */
		else if ($postDate && $expiryDate && $expiryDate <= $currentTime)
		{
			return 'expired';
		}
		/* end BLOCKSPRO ONLY */
		else
		{
			return 'draft';
		}
	}
}
