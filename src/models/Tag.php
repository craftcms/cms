<?php
namespace craft\app\models;
use craft\app\enums\ElementType;

/**
 * Tag model class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.models
 * @since     3.0
 */
class Tag extends BaseElementModel
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

	// Deprecated Methods
	// -------------------------------------------------------------------------

	/**
	 * Returns the tag's title.
	 *
	 * @deprecated Deprecated in 2.3. Use [[$title]] instead.
	 * @return string
	 *
	 * @todo Remove this method in Craft 4.
	 */
	public function getName()
	{
		craft()->deprecator->log('Tag::name', 'The TagModel ‘name’ property has been deprecated. Use ‘title’ instead.');
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
