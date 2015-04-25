<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\base\Model;

/**
 * CategoryGroup model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class CategoryGroup extends Model
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
	 * @var integer Structure ID
	 */
	public $structureId;

	/**
	 * @var integer Field layout ID
	 */
	public $fieldLayoutId;

	/**
	 * @var string Name
	 */
	public $name;

	/**
	 * @var string Handle
	 */
	public $handle;

	/**
	 * @var boolean Has URLs
	 */
	public $hasUrls = true;

	/**
	 * @var string Template
	 */
	public $template;

	/**
	 * @var integer Max levels
	 */
	public $maxLevels;


	/**
	 * @var The element type that category groups' field layouts should be associated with.
	 */
	private $_fieldLayoutElementType = 'craft\app\elements\Category';

	/**
	 * @var
	 */
	private $_locales;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['id'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['structureId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['fieldLayoutId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['maxLevels'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['id', 'structureId', 'fieldLayoutId', 'name', 'handle', 'hasUrls', 'template', 'maxLevels'], 'safe', 'on' => 'search'],
		];
	}

	/**
	 * Use the translated category group's name as the string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return Craft::t('app', $this->name);
	}

	/**
	 * Returns the category's locale models.
	 *
	 * @return array
	 */
	public function getLocales()
	{
		if (!isset($this->_locales))
		{
			if ($this->id)
			{
				$this->_locales = Craft::$app->getCategories()->getGroupLocales($this->id, 'locale');
			}
			else
			{
				$this->_locales = [];
			}
		}

		return $this->_locales;
	}

	/**
	 * Sets the section's locale models.
	 *
	 * @param array $locales
	 *
	 * @return null
	 */
	public function setLocales($locales)
	{
		$this->_locales = $locales;
	}
}
