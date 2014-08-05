<?php
namespace Craft;

/**
 * Class SectionLocaleRecord
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.records
 * @since     1.0
 */
class SectionLocaleRecord extends BaseRecord
{
	////////////////////
	// PUBLIC METHODS
	////////////////////

	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'sections_i18n';
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'section' => array(static::BELONGS_TO, 'SectionRecord', 'required' => true, 'onDelete' => static::CASCADE),
			'locale'  => array(static::BELONGS_TO, 'LocaleRecord', 'locale', 'required' => true, 'onDelete' => static::CASCADE, 'onUpdate' => static::CASCADE),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('sectionId', 'locale'), 'unique' => true),
		);
	}

	////////////////////
	// PROTECTED METHODS
	////////////////////

	/**
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'locale'           => array(AttributeType::Locale, 'required' => true),
			'enabledByDefault' => array(AttributeType::Bool, 'default' => true),
			'urlFormat'        => AttributeType::UrlFormat,
			'nestedUrlFormat'  => AttributeType::UrlFormat,
		);
	}
}
