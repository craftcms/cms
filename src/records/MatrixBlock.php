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
	 * @inheritdoc
	 *
	 * @return string
	 */
	public static function tableName()
	{
		return '{{%matrixblocks}}';
	}

	/**
	 * @inheritDoc BaseRecord::defineRelations()
	 *
	 * @return array
	 */
	public function defineRelations()
	{
		return [
			'element'     => [static::BELONGS_TO, 'Element', 'id', 'required' => true, 'onDelete' => static::CASCADE],
			'owner'       => [static::BELONGS_TO, 'Element', 'required' => true, 'onDelete' => static::CASCADE],
			'ownerLocale' => [static::BELONGS_TO, 'Locale', 'ownerLocale', 'onDelete' => static::CASCADE, 'onUpdate' => static::CASCADE],
			'field'       => [static::BELONGS_TO, 'Field', 'required' => true, 'onDelete' => static::CASCADE],
			'type'        => [static::BELONGS_TO, 'MatrixBlockType', 'onDelete' => static::CASCADE],
		];
	}

	/**
	 * @inheritDoc BaseRecord::defineIndexes()
	 *
	 * @return array
	 */
	public function defineIndexes()
	{
		return [
			['columns' => ['ownerId']],
			['columns' => ['fieldId']],
			['columns' => ['typeId']],
			['columns' => ['sortOrder']],
		];
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
