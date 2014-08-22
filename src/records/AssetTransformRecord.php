<?php
namespace Craft;

/**
 * Class AssetTransformRecord
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.records
 * @since     1.0
 */
class AssetTransformRecord extends BaseRecord
{
	// Public Methods
	// =========================================================================

	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'assettransforms';
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('name'), 'unique' => true),
			array('columns' => array('handle'), 'unique' => true),
		);
	}

	/**
	 * @return array
	 */
	public function scopes()
	{
		return array(
			'ordered' => array('order' => 'name'),
		);
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'name'                => array(AttributeType::String, 'required' => true),
			'handle'              => array(AttributeType::Handle, 'required' => true),
			'mode'                => array(AttributeType::Enum, 'required' => true, 'values' => array('stretch', 'fit', 'crop'), 'default' => 'crop'),
			'position'            => array(AttributeType::Enum, 'values' => array('top-left', 'top-center', 'top-right', 'center-left', 'center-center', 'center-right', 'bottom-left', 'bottom-center', 'bottom-right'), 'required' => true, 'default' => 'center-center'),
			'height'              => AttributeType::Number,
			'width'               => AttributeType::Number,
			'format'              => AttributeType::String,
			'quality'             => array(AttributeType::Number, 'required' => false),
			'dimensionChangeTime' => AttributeType::DateTime
		);
	}
}
