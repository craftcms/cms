<?php
namespace Blocks;

/**
 * Entry model class
 *
 * Used for transporting entry data throughout the system.
 */
class EntryModel extends BaseBlockEntityModel
{
	/**
	 * Use the entry title as its string representation.
	 *
	 * @return string
	 */
	function __toString()
	{
		return $this->title;
	}

	public function defineAttributes()
	{
		$attributes['id'] = AttributeType::Number;
		$attributes['title'] = AttributeType::String;
		$attributes['slug'] = AttributeType::String;
		$attributes['postDate'] = AttributeType::DateTime;
		$attributes['expiryDate'] = AttributeType::DateTime;
		$attributes['enabled'] = AttributeType::Bool;
		$attributes['tags'] = AttributeType::String;

		if (Blocks::hasPackage(BlocksPackage::Users))
		{
			$attributes['authorId'] = AttributeType::Number;
		}

		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			$attributes['sectionId'] = AttributeType::Number;
		}

		if (Blocks::hasPackage(BlocksPackage::Language))
		{
			$attributes['language'] = AttributeType::Language;
		}

		return $attributes;
	}

	/**
	 * Returns the entry's status.
	 *
	 * @return string
	 */
	public function getStatus()
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

	/**
	 * Returns the entry's section.
	 *
	 * @return SectionModel|null
	 */
	public function getSection()
	{
		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			return blx()->sections->getSectionById($this->sectionId);
		}
	}

	/**
	 * Returns the entry's author.
	 *
	 * @return UserModel|null
	 */
	public function getAuthor()
	{
		if (Blocks::hasPackage(BlocksPackage::Users))
		{
			return blx()->account->getUserById($this->authorId);
		}
	}

	/**
	 * Returns the entry's front end URL.
	 *
	 * @return string
	 */
	public function getUrl()
	{
		return '#';
	}

	/**
	 * Returns the entry's CP edit URL
	 *
	 * @return string
	 */
	public function getCpEditUrl()
	{
		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			$section = $this->getSection();

			if ($section)
			{
				$path = 'content/'.$section->handle.'/'.$this->id;
			}
		}
		else
		{
			$path = 'content/blog/'.$this->id;
		}

		if (!empty($path))
		{
			return UrlHelper::getUrl($path);
		}
	}

	/**
	 * Returns a list of EntryTag models for this EntryModel.
	 *
	 * @return mixed
	 */
	public function getTags()
	{
		if (!$this->tags)
		{
			$this->tags = blx()->entries->getTagsForEntryById($this->id);
		}

		return $this->tags;
	}
}
