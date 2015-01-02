<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use craft\app\enums\AttributeType;

/**
 * Folders parameters.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FolderCriteria extends BaseModel
{
	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseModel::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'id'       => AttributeType::Number,
			'parentId' => array(AttributeType::Number, 'default' => false),
			'sourceId' => AttributeType::Number,
			'name'     => AttributeType::String,
			'path'     => AttributeType::String,
			'order'    => array(AttributeType::String, 'default' => 'name asc'),
			'offset'   => AttributeType::Number,
			'limit'    => AttributeType::Number,
		);
	}
}
