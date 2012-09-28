<?php
namespace Blocks;

/**
 * Entry package class
 *
 * Used for transporting entry data throughout the system.
 */
class EntryPackage extends BasePackage
{
	public $authorId;
	public $sectionId;
	public $language;
	public $title;
	public $slug;
	public $postDate;
	public $expiryDate;
	public $blocks;
	public $enabled;

	public $blockErrors;

	/**
	 * Saves the entry.
	 *
	 * @return bool
	 */
	public function save()
	{
		return blx()->entries->saveEntry($this);
	}

	/**
	 * Returns the entry's status.
	 */
	public function status()
	{
		if ($this->enabled)
		{
			$currentTime = DateTimeHelper::currentTime();
			$postDate = ($this->postDate ? $this->postDate->getTimestamp() : null);
			$expiryDate = ($this->expiryDate ? $this->expiryDate->getTimestamp() : null);

			if ($postDate <= $currentTime && (!$expiryDate || $expiryDate > $currentTime))
			{
				return 'live';
			}
			else if ($postDate && $postDate > $currentTime)
			{
				return 'pending';
			}
			/* HIDE */
			//else if ($expiryDate && $expiryDate <= $currentTime)
			/* end HIDE */
			else
			{
				return 'expired';
			}
		}
		else
		{
			return 'disabled';
		}
	}
}
