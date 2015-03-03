<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\enums\AttributeType;
use craft\app\enums\ElementType;
use craft\app\helpers\UrlHelper;
use craft\app\models\CategoryGroup as CategoryGroupModel;
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
	 * @var integer ID
	 */
	public $id;

	/**
	 * @var boolean Enabled
	 */
	public $enabled = true;

	/**
	 * @var boolean Archived
	 */
	public $archived = false;

	/**
	 * @var string Locale
	 */
	public $locale = 'en-US';

	/**
	 * @var boolean Locale enabled
	 */
	public $localeEnabled = true;

	/**
	 * @var string Slug
	 */
	public $slug;

	/**
	 * @var string URI
	 */
	public $uri;

	/**
	 * @var \DateTime Date created
	 */
	public $dateCreated;

	/**
	 * @var \DateTime Date updated
	 */
	public $dateUpdated;

	/**
	 * @var integer Root
	 */
	public $root;

	/**
	 * @var integer Lft
	 */
	public $lft;

	/**
	 * @var integer Rgt
	 */
	public $rgt;

	/**
	 * @var integer Level
	 */
	public $level;

	/**
	 * @var integer Group ID
	 */
	public $groupId;

	/**
	 * @var integer New parent ID
	 */
	public $newParentId;


	/**
	 * @var string
	 */
	protected $elementType = ElementType::Category;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'slug' => Craft::t('app', 'Slug'),
			'uri' => Craft::t('app', 'URI'),
		];
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['id'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['locale'], 'craft\\app\\validators\\Locale'],
			[['dateCreated'], 'craft\\app\\validators\\DateTime'],
			[['dateUpdated'], 'craft\\app\\validators\\DateTime'],
			[['root'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['lft'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['rgt'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['level'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['groupId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['newParentId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['id', 'enabled', 'archived', 'locale', 'localeEnabled', 'slug', 'uri', 'dateCreated', 'dateUpdated', 'root', 'lft', 'rgt', 'level', 'groupId', 'newParentId'], 'safe', 'on' => 'search'],
		];
	}

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
		return Craft::$app->getUser()->checkPermission('editCategories:'.$this->groupId);
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
			return Craft::$app->categories->getGroupById($this->groupId);
		}
	}
}
