<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\elements;

use Craft;
use craft\app\base\Element;
use craft\app\base\FieldLayoutTrait;
use craft\app\db\Query;
use craft\app\enums\AttributeType;
use craft\app\helpers\DbHelper;
use craft\app\models\ElementCriteria as ElementCriteriaModel;

/**
 * The GlobalSet class is responsible for implementing and defining globals as a native element type in
 * Craft.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class GlobalSet extends Element
{
	// Traits
	// =========================================================================

	use FieldLayoutTrait;

	// Properties
	// =========================================================================

	/**
	 * @var string Name
	 */
	public $name;

	/**
	 * @var string Handle
	 */
	public $handle;

	/**
	 * @var integer Field layout ID
	 */
	public $fieldLayoutId;

	/**
	 * @var The element type that global sets' field layouts should be associated with.
	 */
	private $_fieldLayoutElementType = ElementType::GlobalSet;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc ElementInterface::hasContent()
	 *
	 * @return bool
	 */
	public static function hasContent()
	{
		return true;
	}

	/**
	 * @inheritDoc ElementInterface::isLocalized()
	 *
	 * @return bool
	 */
	public static function isLocalized()
	{
		return true;
	}

	/**
	 * @inheritDoc ElementInterface::defineCriteriaAttributes()
	 *
	 * @return array
	 */
	public static function defineCriteriaAttributes()
	{
		return [
			'handle' => AttributeType::Mixed,
			'order' => [AttributeType::String, 'default' => 'name'],
		];
	}

	/**
	 * @inheritDoc ElementInterface::modifyElementsQuery()
	 *
	 * @param Query                $query
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return mixed
	 */
	public static function modifyElementsQuery(Query $query, ElementCriteriaModel $criteria)
	{
		$query
			->addSelect('globalsets.name, globalsets.handle, globalsets.fieldLayoutId')
			->innerJoin('{{%globalsets}} globalsets', 'globalsets.id = elements.id');

		if ($criteria->handle)
		{
			$query->andWhere(DbHelper::parseParam('globalsets.handle', $criteria->handle, $query->params));
		}
	}

	/**
	 * @inheritDoc ElementInterface::populateElementModel()
	 *
	 * @param array $row
	 *
	 * @return array
	 */
	public static function populateElementModel($row)
	{
		return GlobalSet::populateModel($row);
	}

	// Instance Methods
	// -------------------------------------------------------------------------

	/**
	 * Use the global set's name as its string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->name;
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		$rules = parent::rules();

		$rules[] = [['handle'], 'craft\\app\\validators\\Handle', 'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']];
		$rules[] = [['fieldLayoutId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true];
		$rules[] = [['name', 'handle'], 'string', 'max' => 255];

		return $rules;
	}

	/**
	 * @inheritDoc BaseElementModel::getCpEditUrl()
	 *
	 * @return string|false
	 */
	public function getCpEditUrl()
	{
		return UrlHelper::getCpUrl('globals/'.$this->handle);
	}
}
