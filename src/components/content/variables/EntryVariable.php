<?php
namespace Blocks;

/**
 * Entry template variable
 */
class EntryVariable extends ModelVariable
{
	/**
	 * Returns the entry's status (live, pending, expired, offline).
	 *
	 * @return string
	 */
	public function status()
	{
		return $this->model->getStatus();
	}

	/**
	 * Returns whether the entry is live.
	 *
	 * @return bool
	 */
	public function live()
	{
		return $this->model->isLive();
	}

	/**
	 * Returns whether the entry is offline.
	 *
	 * @return bool
	 */
	public function offline()
	{
		return $this->model->isOffline();
	}

	/**
	 * Returns whether the entry has been published.
	 *
	 * @return bool
	 */
	public function published()
	{
		return $this->model->isPublished();
	}

	/**
	 * Returns whether the entry is pending.
	 *
	 * @return bool
	 */
	public function pending()
	{
		return $this->model->isPending();
	}

	/* BLOCKSPRO ONLY */
	/**
	 * Returns whether the entry has expired.
	 *
	 * @return bool
	 */
	public function expired()
	{
		return $this->model->hasExpired();
	}
	/* end BLOCKSPRO ONLY */

	/**
	 * Returns the entry's URL.
	 *
	 * @return string
	 */
	public function url()
	{
		return $this->model->getUrl();
	}

	/* BLOCKSPRO ONLY */
	/**
	 * Returns the entry's section.
	 *
	 * @return SectionVariable
	 */
	public function section()
	{
		$record = $this->model->section;
		if ($record)
			return new SectionVariable($record);
	}

	/**
	 * Returns the entry's author.
	 *
	 * @return UserVariable
	 */
	public function author()
	{
		$record = $this->model->author;
		if ($record)
			return new UserVariable($record);
	}
	/* end BLOCKSPRO ONLY */
}
