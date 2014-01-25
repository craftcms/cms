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
			'groupId' => AttributeType::Number,
			'name'    => AttributeType::String,
		));
	}

	/**
	 * Returns whether the current user can edit the element.
	 *
	 * @return bool
	 */
	public function isEditable()
	{
		return true;
	}

	/**
	 * Returns the field layout used by this element.
	 *
	 * @return FieldLayoutModel|null
	 */
	public function getFieldLayout()
	{
		$tagGroup = $this->getGroup();

		if ($tagGroup)
		{
			return $tagGroup->getFieldLayout();
		}
	}

	/**
	 * Returns the tag's group.
	 *
	 * @return TagGroupModel|null
	 */
	public function getGroup()
	{
		if ($this->groupId)
		{
			return craft()->tags->getTagGroupById($this->groupId);
		}
	}

	// Deprecated functions

	/**
	 * Returns the tag group's ID.
	 *
	 * @return int|null
	 * @deprecated Deprecated since 1.4
	 */
	public function setId()
	{
		return $this->groupId;
	}

	/**
	 * Returns the tag's group.
	 *
	 * @return TagGroupModel|null
	 * @deprecated Deprecated since 1.4
	 */
	public function getSet()
	{
		return $this->getGroup();
	}
}
