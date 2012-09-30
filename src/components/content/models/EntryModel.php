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

	public function defineAttributes()
	{
		$attributes['id'] = AttributeType::Number;
		$attributes['authorId'] = AttributeType::Number;
		$attributes['sectionId'] = AttributeType::Number;
		$attributes['title'] = AttributeType::String;
		$attributes['slug'] = AttributeType::String;
		$attributes['postDate'] = AttributeType::DateTime;
		$attributes['expiryDate'] = AttributeType::DateTime;
		$attributes['blocks'] = AttributeType::Mixed;
		$attributes['enabled'] = AttributeType::Bool;

		if (Blocks::hasPackage(BlocksPackage::Language))
		{
			$attributes['language'] = AttributeType::Language;
		}

		return $attributes;
	}

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
	 * Returns whether there are block errors.
	 *
	 * @return bool
	 */
	public function hasBlockErrors()
	{
		return !empty($this->_blockErrors);
	}

	/**
	 * Returns the errors for all block attributes.
	 *
	 * @return array
	 */
	public function getBlockErrors()
	{
		return $this->_blockErrors;
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
}
