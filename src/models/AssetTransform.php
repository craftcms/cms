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
 * The AssetTransform model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AssetTransform extends Model
{
	// Properties
	// =========================================================================

	/**
	 * @var integer ID
	 */
	public $id;

	/**
	 * @var string Name
	 */
	public $name;

	/**
	 * @var string Handle
	 */
	public $handle;

	/**
	 * @var integer Width
	 */
	public $width;

	/**
	 * @var integer Height
	 */
	public $height;

	/**
	 * @var string Format
	 */
	public $format;

	/**
	 * @var \DateTime Dimension change time
	 */
	public $dimensionChangeTime;

	/**
	 * @var string Mode
	 */
	public $mode = 'crop';

	/**
	 * @var string Position
	 */
	public $position = 'center-center';

	/**
	 * @var integer Quality
	 */
	public $quality;

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
			[['width'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['height'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['dimensionChangeTime'], 'craft\\app\\validators\\DateTime'],
			[['quality'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['handle'], 'string', 'max' => 255],
			[['id', 'name', 'handle', 'width', 'height', 'format', 'dimensionChangeTime', 'mode', 'position', 'quality'], 'safe', 'on' => 'search'],
		];
	}

	/**
	 * Use the folder name as the string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->name;
	}

	/**
	 * Return true if the transform is a named one.
	 *
	 * @return bool
	 */
	public function isNamedTransform()
	{
		return !empty($this->name);
	}

	/**
	 * Get a list of transform modes.
	 *
	 * @return array
	 */
	public static function getTransformModes()
	{
		return [
			'crop'    => Craft::t('app', 'Scale and crop'),
			'fit'     => Craft::t('app', 'Scale to fit'),
			'stretch' => Craft::t('app', 'Stretch to fit')
		];
	}

	/**
	 * @inheritdoc
	 */
	public function datetimeAttributes()
	{
		$names = parent::datetimeAttributes();
		$names[] = 'dimensionChangeTime';
		return $names;
	}
}
