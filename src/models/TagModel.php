<?php
namespace Craft;

/**
 * Tag model class
 */
class TagModel extends BaseElementModel
{
	protected $elementType = ElementType::Tag;

	/**
	 * Use the tag name as its string representation.
	 *
	 * @return string
	 */
	function __toString()
	{
		return $this->name;
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array_merge(parent::defineAttributes(), array(
			'setId' => AttributeType::Number,
			'name'  => AttributeType::String,
		));
	}

	/**
	 * Returns the field layout used by this element.
	 *
	 * @return FieldLayoutModel|null
	 */
	public function getFieldLayout()
	{
		$tagSet = $this->getSet();

		if ($tagSet)
		{
			return $tagSet->getFieldLayout();
		}
	}

	/**
	 * Returns the tag's set.
	 *
	 * @return TagSetModel|null
	 */
	public function getSet()
	{
		if ($this->setId)
		{
			return craft()->tags->getTagSetById($this->setId);
		}
	}
}
