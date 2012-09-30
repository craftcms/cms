<?php
namespace Blocks;

/**
 *
 */
class EntryVariable extends BaseModelVariable
{
	/**
	 * Use the entry title as its string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->model->title;
	}

	/**
	 * Returns the entry's status.
	 *
	 * @return string
	 */
	public function status()
	{
		if ($this->model->enabled)
		{
			$currentTime = DateTimeHelper::currentTime();
			$postDate = ($this->model->postDate ? $this->model->postDate->getTimestamp() : null);
			$expiryDate = ($this->model->expiryDate ? $this->model->expiryDate->getTimestamp() : null);

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

	/**
	 * Returns the entry's block errors.
	 *
	 * @return array
	 */
	public function blockErrors()
	{
		return $this->model->getBlockErrors();
	}

	/**
	 * Returns the entry's section
	 *
	 * @return SectionVariable|null
	 */
	public function section()
	{
		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			$section = $this->model->getSection();
			if ($section)
			{
				return new SectionVariable($section);
			}
		}
	}

	/**
	 * Returns the entry's author
	 *
	 * @return UserVariable|null
	 */
	public function author()
	{
		if (Blocks::hasPackage(BlocksPackage::Users))
		{
			$user = $this->model->getAuthor();
			if ($user)
			{
				return new UserVariable($user);
			}
		}
	}
}