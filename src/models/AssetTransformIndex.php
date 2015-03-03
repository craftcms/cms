<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use craft\app\base\Model;
use craft\app\enums\AttributeType;

/**
 * Class AssetTransformIndex model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AssetTransformIndex extends Model
{
	// Properties
	// =========================================================================

	/**
	 * @var integer ID
	 */
	public $id;

	/**
	 * @var integer File ID
	 */
	public $fileId;

	/**
	 * @var integer Filename
	 */
	public $filename;

	/**
	 * @var integer Format
	 */
	public $format;

	/**
	 * @var string Location
	 */
	public $location;

	/**
	 * @var integer Source ID
	 */
	public $sourceId;

	/**
	 * @var boolean File exists
	 */
	public $fileExists = false;

	/**
	 * @var boolean In progress
	 */
	public $inProgress = false;

	/**
	 * @var \DateTime Date indexed
	 */
	public $dateIndexed;

	/**
	 * @var \DateTime Date updated
	 */
	public $dateUpdated;

	/**
	 * @var \DateTime Date created
	 */
	public $dateCreated;

	/**
	 * @var integer Detected format
	 */
	public $detectedFormat;

	/**
	 * @var array Transform
	 */
	public $transform;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['id'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['fileId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['filename'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['format'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['sourceId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['dateIndexed'], 'craft\\app\\validators\\DateTime'],
			[['dateUpdated'], 'craft\\app\\validators\\DateTime'],
			[['dateCreated'], 'craft\\app\\validators\\DateTime'],
			[['detectedFormat'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['id', 'fileId', 'filename', 'format', 'location', 'sourceId', 'fileExists', 'inProgress', 'dateIndexed', 'dateUpdated', 'dateCreated', 'detectedFormat', 'transform'], 'safe', 'on' => 'search'],
		];
	}

	/**
	 * Use the folder name as the string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->id;
	}
}
