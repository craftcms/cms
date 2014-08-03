<?php
namespace Craft;

/**
 * Tag model class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.models
 * @since     1.1
 */
class TagModel extends BaseElementModel
{
	protected $elementType = ElementType::Tag;

	/**
	 * Use the tag name as its string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->name;
	}

	/**
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
	 * @deprecated Deprecated in 2.0.
	 * @return int|null
	 */
	public function setId()
	{
		craft()->deprecator->log('TagModel::setId', 'Tags’ ‘setId’ property has been deprecated. Use ‘groupId’ instead.');
		return $this->groupId;
	}

	/**
	 * Returns the tag's group.
	 *
	 * @deprecated Deprecated in 2.0.
	 * @return TagGroupModel|null
	 */
	public function getSet()
	{
		craft()->deprecator->log('TagModel::getSet()', 'TagModel::getSet() has been deprecated. Use getGroup() instead.');
		return $this->getGroup();
	}
}
