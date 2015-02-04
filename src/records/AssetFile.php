<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use craft\app\enums\AttributeType;
use craft\app\enums\ColumnType;

/**
 * Class AssetFile record.
 *
 * @todo Create save function which calls parent::save and then updates the meta data table (keywords, author, etc)
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AssetFile extends BaseRecord
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
		return '{{%assetfiles}}';
	}

	/**
	 * @inheritDoc BaseRecord::defineRelations()
	 *
	 * @return array
	 */
	public function defineRelations()
	{
		return [
			'element' => [static::BELONGS_TO, 'Element', 'id', 'required' => true, 'onDelete' => static::CASCADE],
			'source'  => [static::BELONGS_TO, 'AssetSource', 'required' => false, 'onDelete' => static::CASCADE],
			'folder'  => [static::BELONGS_TO, 'AssetFolder', 'required' => true, 'onDelete' => static::CASCADE],
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
			['columns' => ['filename', 'folderId'], 'unique' => true],
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
			'filename'		=> [AttributeType::String, 'required' => true],
			'kind'			=> ['column' => ColumnType::Varchar, 'maxLength' => 50, 'required' => true, 'default' => 'unknown'],
			'width'			=> [AttributeType::Number, 'min' => 0, 'column' => ColumnType::SmallInt],
			'height'		=> [AttributeType::Number, 'min' => 0, 'column' => ColumnType::SmallInt],
			'size'			=> [AttributeType::Number, 'min' => 0, 'column' => ColumnType::Int],
			'dateModified'	=> AttributeType::DateTime
		];
	}
}
