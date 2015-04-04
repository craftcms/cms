<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use craft\app\base\Model;

/**
 * Folders parameters.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FolderCriteria extends Model
{
	// Properties
	// =========================================================================

	/**
	 * @var integer ID
	 */
	public $id;

	/**
	 * @var integer Parent ID
	 */
	public $parentId = false;

	/**
	 * @var integer Source ID
	 */
	public $volumeId;

	/**
	 * @var string Name
	 */
	public $name;

	/**
	 * @var string Path
	 */
	public $path;

	/**
	 * @var string Order
	 */
	public $order = 'name asc';

	/**
	 * @var integer Offset
	 */
	public $offset;

	/**
	 * @var integer Limit
	 */
	public $limit;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['id'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['parentId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['sourceId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['offset'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['limit'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['id', 'parentId', 'sourceId', 'name', 'path', 'order', 'offset', 'limit'], 'safe', 'on' => 'search'],
		];
	}
}
