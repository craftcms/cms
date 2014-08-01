<?php
namespace Craft;

/**
 * Class RouteRecord
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.records
 * @since     1.0
 */
class RouteRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'routes';
	}

	/**
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'locale'     => AttributeType::Locale,
			'urlParts'   => array(AttributeType::String, 'required' => true),
			'urlPattern' => array(AttributeType::String, 'required' => true),
			'template'   => array(AttributeType::String, 'required' => true),
			'sortOrder'  => AttributeType::SortOrder,
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'locale'  => array(static::BELONGS_TO, 'LocaleRecord', 'locale', 'onDelete' => static::CASCADE, 'onUpdate' => static::CASCADE),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('locale')),
			array('columns' => array('urlPattern'), 'unique' => true),
		);
	}
}
