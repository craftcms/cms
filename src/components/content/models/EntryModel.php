<?php
namespace Blocks;

/**
 * Entry model class
 *
 * Used for transporting entry data throughout the system.
 */
class EntryModel extends BaseModel
{
	private $_blockErrors = array();

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
		$attributes['blocks'] = AttributeType::Mixed;
		$attributes['enabled'] = AttributeType::Bool;

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
	 * Returns whether there are block errors.
	 *
	 * @param string|null $attribute
	 * @return bool
	 */
	public function hasBlockErrors($attribute = null)
	{
		if ($attribute === null)
		{
			return $this->_blockErrors !== array();
		}
		else
		{
			return isset($this->_blockErrors[$attribute]);
		}
	}

	/**
	 * Returns the errors for all block attributes.
	 *
	 * @param string|null $attribute
	 * @return array
	 */
	public function getBlockErrors($attribute = null)
	{
		if ($attribute === null)
		{
			return $this->_blockErrors;
		}
		else
		{
			return isset($this->_blockErrors[$attribute]) ? $this->_blockErrors[$attribute] : array();
		}
	}

	/**
	 * Adds a new error to the specified setting attribute.
	 *
	 * @param string $attribute
	 * @param string $error
	 */
	public function addBlockError($attribute,$error)
	{
		$this->_blockErrors[$attribute][] = $error;
	}

	/**
	 * Adds a list of block errors.
	 *
	 * @param array $errors
	 */
	public function addBlockErrors($errors)
	{
		foreach ($errors as $attribute => $error)
		{
			if (is_array($error))
			{
				foreach ($error as $e)
				{
					$this->addBlockError($attribute, $e);
				}
			}
			else
			{
				$this->addBlockError($attribute, $error);
			}
		}
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
}
