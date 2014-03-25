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
	 * @deprecated
	 */
	public function setId()
	{
		craft()->deprecator->deprecate('craft_tagmodel_setid', 'TagModel->setId has been deprecated. Use TagModel->groupId instead.', '1.4');
		return $this->groupId;
	}

	/**
	 * Returns the tag's group.
	 *
	 * @return TagGroupModel|null
	 * @deprecated
	 */
	public function getSet()
	{
		craft()->deprecator->deprecate('craft_tagmodel_getset', 'TagModel->getSet has been deprecated. Use TagModel->getGroup instead.', '1.4');
		return $this->getGroup();
	}
}
