<?php
namespace Craft;

/**
 * Folders parameters.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.models
 * @since     1.0
 */
class FolderCriteriaModel extends BaseModel
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
