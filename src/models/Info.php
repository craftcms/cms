<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use craft\app\enums\AttributeType;
use craft\app\enums\ColumnType;

/**
 * Class Info model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Info extends BaseModel
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
		return [
			'id'            => AttributeType::Number,
			'version'       => [AttributeType::String, 'required' => true, 'default' => '0'],
			'build'         => [AttributeType::Number, 'required' => true, 'default' => '0'],
			'schemaVersion' => [AttributeType::String, 'required' => true, 'default' => '0'],
			'edition'       => [AttributeType::Number, 'required' => true, 'default' => 0],
			'releaseDate'   => [AttributeType::DateTime, 'required' => true],
			'siteName'      => [AttributeType::Name, 'required' => true],
			'siteUrl'       => [AttributeType::Url, 'required' => true],
			'timezone'      => [AttributeType::String, 'maxLength' => 30, 'default' => date_default_timezone_get()],
			'on'            => AttributeType::Bool,
			'maintenance'   => AttributeType::Bool,
			'track'         => [AttributeType::String, 'maxLength' => 40, 'column' => ColumnType::Varchar, 'required' => true],
			'uid'           => AttributeType::String,
		];
	}
}
