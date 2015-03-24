<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use craft\app\enums\AttributeType;

/**
 * AssetIndexData model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AssetIndexData extends BaseComponentModel
{
	// Properties
	// =========================================================================

	/**
	 * @var integer ID
	 */
	public $id;

	/**
	 * @var integer Source ID
	 */
	public $sourceId;

	/**
	 * @var string Session ID
	 */
	public $sessionId;

	/**
	 * @var integer Offset
	 */
	public $offset;

	/**
	 * @var string URI
	 */
	public $uri;

	/**
	 * @var integer Size
	 */
	public $size;

	/**
	 * @var integer Record ID
	 */
	public $recordId;

	/**
	 * @var DateTime The index timestamp
	 */
	public $timestamp;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function datetimeAttributes()
	{
		$names = parent::datetimeAttributes();
		$names[] = 'timestamp';
		return $names;
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['id'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['sourceId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['offset'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['size'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['recordId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['id', 'sourceId', 'sessionId', 'offset', 'uri', 'size', 'recordId'], 'safe', 'on' => 'search'],
		];
	}

	/**
	 * Use the translated source name as the string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->uri;
	}
}
