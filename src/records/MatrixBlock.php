<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use craft\app\enums\AttributeType;

/**
 * Class MatrixBlock record.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class MatrixBlock extends BaseRecord
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseRecord::getTableName()
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'matrixblocks';
	}

	/**
	 * @inheritDoc BaseRecord::defineRelations()
	 *
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'element'     => array(static::BELONGS_TO, 'Element', 'id', 'required' => true, 'onDelete' => static::CASCADE),
			'owner'       => array(static::BELONGS_TO, 'Element', 'required' => true, 'onDelete' => static::CASCADE),
			'ownerLocale' => array(static::BELONGS_TO, 'Locale', 'ownerLocale', 'onDelete' => static::CASCADE, 'onUpdate' => static::CASCADE),
			'field'       => array(static::BELONGS_TO, 'Field', 'required' => true, 'onDelete' => static::CASCADE),
			'type'        => array(static::BELONGS_TO, 'MatrixBlockType', 'onDelete' => static::CASCADE),
		);
	}

	/**
	 * @inheritDoc BaseRecord::defineIndexes()
	 *
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('ownerId')),
			array('columns' => array('fieldId')),
			array('columns' => array('typeId')),
			array('columns' => array('sortOrder')),
		);
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseRecord::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return [
			'sortOrder' => AttributeType::SortOrder,
			'ownerLocale' => AttributeType::Locale,
		];
	}
}
