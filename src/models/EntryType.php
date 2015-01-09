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
use craft\app\models\Section as SectionModel;

/**
 * EntryType model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class EntryType extends BaseModel
{
	// Traits
	// =========================================================================

	use \craft\app\base\FieldLayoutTrait;

	// Properties
	// =========================================================================

	/**
	 * @var The element type that entry types' field layouts should be associated with.
	 */
	private $_fieldLayoutElementType = ElementType::Entry;

	// Public Methods
	// =========================================================================

	/**
	 * Use the handle as the string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->handle;
	}

	/**
	 * @inheritDoc BaseElementModel::getCpEditUrl()
	 *
	 * @return string
	 */
	public function getCpEditUrl()
	{
		return UrlHelper::getCpUrl('settings/sections/'.$this->sectionId.'/entrytypes/'.$this->id);
	}

	/**
	 * Returns the entry type’s section.
	 *
	 * @return SectionModel|null
	 */
	public function getSection()
	{
		if ($this->sectionId)
		{
			return Craft::$app->sections->getSectionById($this->sectionId);
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
		return [
			'id'            => AttributeType::Number,
			'sectionId'     => AttributeType::Number,
			'fieldLayoutId' => AttributeType::Number,
			'name'          => AttributeType::String,
			'handle'        => AttributeType::String,
			'hasTitleField' => [AttributeType::Bool, 'default' => true],
			'titleLabel'    => [AttributeType::String, 'default' => Craft::t('Title')],
			'titleFormat'   => AttributeType::String,
		];
	}
}
