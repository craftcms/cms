<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\assetsourcetypes\BaseAssetSourceType;

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
	 * @var integer ID
	 */
	public $id;

	/**
	 * @var string Type
	 */
	public $type = 'Local';

	/**
	 * @var array Settings
	 */
	public $settings;

	/**
	 * @var string Name
	 */
	public $name;

	/**
	 * @var string Handle
	 */
	public $handle;

	/**
	 * @var string Sort order
	 */
	public $sortOrder;

	/**
	 * @var integer Field layout ID
	 */
	public $fieldLayoutId;


	/**
	 * @var The element type that asset sources' field layouts should be associated with.
	 */
	private $_fieldLayoutElementType = 'craft\app\elements\Asset';

	/**
	 * @var
	 */
	private $_sourceType;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['id'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['handle'], 'craft\\app\\validators\\Handle', 'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']],
			[['fieldLayoutId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['handle'], 'string', 'max' => 255],
			[['id', 'type', 'settings', 'name', 'handle', 'sortOrder', 'fieldLayoutId'], 'safe', 'on' => 'search'],
		];
	}

	/**
	 * Use the translated source name as the string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return Craft::t('app', $this->name);
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
}
