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
		return array(
			'id'            => AttributeType::Number,
			'version'       => array(AttributeType::String, 'required' => true, 'default' => '0'),
			'build'         => array(AttributeType::Number, 'required' => true, 'default' => '0'),
			'schemaVersion' => array(AttributeType::String, 'required' => true, 'default' => '0'),
			'edition'       => array(AttributeType::Number, 'required' => true, 'default' => 0),
			'releaseDate'   => array(AttributeType::DateTime, 'required' => true),
			'siteName'      => array(AttributeType::Name, 'required' => true),
			'siteUrl'       => array(AttributeType::Url, 'required' => true),
			'timezone'      => array(AttributeType::String, 'maxLength' => 30, 'default' => date_default_timezone_get()),
			'on'            => AttributeType::Bool,
			'maintenance'   => AttributeType::Bool,
			'track'         => array(AttributeType::String, 'maxLength' => 40, 'column' => ColumnType::Varchar, 'required' => true),
			'uid'           => AttributeType::String,
		);
	}
}
