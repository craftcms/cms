<?php
namespace Craft;

/**
 * Tag model class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.models
 * @since     1.1
 */
class TagModel extends BaseElementModel
{
	// Properties
	// =========================================================================

	/**
	 * @var string
	 */
	protected $elementType = ElementType::Tag;

	// Public Methods
	// =========================================================================

	/**
	 * Use the tag title as its string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->getContent()->title;
	}

	/**
	 * @inheritDoc BaseElementModel::isEditable()
	 *
	 * @return bool
	 */
	public function isEditable()
	{
		return true;
	}

	/**
	 * @inheritDoc BaseElementModel::getFieldLayout()
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
	 * @deprecated Deprecated in 2.0. Use 'groupId' instead.
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
	 * @deprecated Deprecated in 2.0. Use {@link getGroup()} instead.
	 * @return TagGroupModel|null
	 */
	public function getSet()
	{
		craft()->deprecator->log('TagModel::getSet()', 'TagModel::getSet() has been deprecated. Use getGroup() instead.');
		return $this->getGroup();
	}

	/**
	 * Returns the tag's title.
	 *
	 * @deprecated Deprecated in 2.3. Use {@link $title} instead.
	 * @return string
	 */
	public function getName()
	{
        craft()->deprecator->log('TagModel::name', 'The Tag ‘name’ property has been deprecated. Use ‘title’ instead.');

		return $this->getContent()->title;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseModel::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array_merge(parent::defineAttributes(), array(
			'groupId' => AttributeType::Number,
		));
	}
}
