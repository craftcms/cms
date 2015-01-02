<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use craft\app\enums\ElementType;
use craft\app\models\FieldLayout as FieldLayoutModel;

/**
 * Category model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Category extends BaseElementModel
{
	// Properties
	// =========================================================================

	/**
	 * @var string
	 */
	protected $elementType = ElementType::Category;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseElementModel::getFieldLayout()
	 *
	 * @return FieldLayoutModel|null
	 */
	public function getFieldLayout()
	{
		$group = $this->getGroup();

		if ($group)
		{
			return $group->getFieldLayout();
		}
	}

	/**
	 * @inheritDoc BaseElementModel::getUrlFormat()
	 *
	 * @return string|null
	 */
	public function getUrlFormat()
	{
		$group = $this->getGroup();

		if ($group && $group->hasUrls)
		{
			$groupLocales = $group->getLocales();

			if (isset($groupLocales[$this->locale]))
			{
				if ($this->level > 1)
				{
					return $groupLocales[$this->locale]->nestedUrlFormat;
				}
				else
				{
					return $groupLocales[$this->locale]->urlFormat;
				}
			}
		}
	}

	/**
	 * @inheritDoc BaseElementModel::isEditable()
	 *
	 * @return bool
	 */
	public function isEditable()
	{
		return craft()->userSession->checkPermission('editCategories:'.$this->groupId);
	}

	/**
	 * @inheritDoc BaseElementModel::getCpEditUrl()
	 *
	 * @return string|false
	 */
	public function getCpEditUrl()
	{
		$group = $this->getGroup();

		if ($group)
		{
			return UrlHelper::getCpUrl('categories/'.$group->handle.'/'.$this->id.($this->slug ? '-'.$this->slug : ''));
		}
	}

	/**
	 * Returns the category's group.
	 *
	 * @return CategoryGroupModel|null
	 */
	public function getGroup()
	{
		if ($this->groupId)
		{
			return craft()->categories->getGroupById($this->groupId);
		}
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

			// Just used for saving categories
			'newParentId'      => AttributeType::Number,
		));
	}
}
