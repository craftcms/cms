<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\assetsourcetypes\BaseAssetSourceType;
use craft\app\enums\AttributeType;
use craft\app\enums\ElementType;

/**
 * The AssetSource model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AssetSource extends BaseComponentModel
{
	// Traits
	// =========================================================================

	use \craft\app\base\FieldLayoutTrait;

	// Properties
	// =========================================================================

	/**
	 * @var The element type that asset sources' field layouts should be associated with.
	 */
	private $_fieldLayoutElementType = ElementType::Asset;

	/**
	 * @var
	 */
	private $_sourceType;

	// Public Methods
	// =========================================================================

	/**
	 * Use the translated source name as the string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return Craft::t($this->name);
	}

	/**
	 * Returns the source type this source is using.
	 *
	 * @return BaseAssetSourceType|null
	 */
	public function getSourceType()
	{
		if (!isset($this->_sourceType))
		{
			$this->_sourceType = Craft::$app->assetSources->populateSourceType($this);

			// Might not actually exist
			if (!$this->_sourceType)
			{
				$this->_sourceType = false;
			}
		}

		// Return 'null' instead of 'false' if it doesn't exist
		if ($this->_sourceType)
		{
			return $this->_sourceType;
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
		$attributes = parent::defineAttributes();

		$attributes['name']            = AttributeType::String;
		$attributes['handle']          = AttributeType::Handle;
		$attributes['type']['default'] = 'Local';
		$attributes['sortOrder']       = AttributeType::String;
		$attributes['fieldLayoutId']   = AttributeType::Number;

		return $attributes;
	}
}
