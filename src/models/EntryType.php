<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\base\ElementInterface;
use craft\app\base\Model;
use craft\app\helpers\UrlHelper;
use craft\app\models\Section as SectionModel;

/**
 * EntryType model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class EntryType extends Model
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
	 * @var integer Section ID
	 */
	public $sectionId;

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
	 * @var boolean Has title field
	 */
	public $hasTitleField = true;

	/**
	 * @var string Title label
	 */
	public $titleLabel = 'Title';

	/**
	 * @var string Title format
	 */
	public $titleFormat;


	/**
	 * @var The element type that entry types' field layouts should be associated with.
	 */
	private $_fieldLayoutElementType = 'craft\app\elements\Entry';

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['id'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['sectionId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['fieldLayoutId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['id', 'sectionId', 'fieldLayoutId', 'name', 'handle', 'hasTitleField', 'titleLabel', 'titleFormat'], 'safe', 'on' => 'search'],
		];
	}

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
	 * Returns the entry’s CP edit URL.
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
			return Craft::$app->getSections()->getSectionById($this->sectionId);
		}
	}
}
