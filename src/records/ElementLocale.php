<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use craft\app\enums\AttributeType;

/**
 * Element locale data record class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ElementLocale extends BaseRecord
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
		return 'elements_i18n';
	}

	/**
	 * @inheritDoc BaseRecord::defineRelations()
	 *
	 * @return array
	 */
	public function defineRelations()
	{
		return [
			'element' => [static::BELONGS_TO, 'Element', 'required' => true, 'onDelete' => static::CASCADE],
			'locale'  => [static::BELONGS_TO, 'Locale', 'locale', 'required' => true, 'onDelete' => static::CASCADE, 'onUpdate' => static::CASCADE],
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
			['columns' => ['elementId', 'locale'], 'unique' => true],
			['columns' => ['slug', 'locale']],
			['columns' => ['uri', 'locale'], 'unique' => true],
			['columns' => ['enabled']],
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
			'locale'  => [AttributeType::Locale, 'required' => true],
			'slug'    => [AttributeType::String],
			'uri'     => [AttributeType::Uri, 'label' => 'URI'],
			'enabled' => [AttributeType::Bool, 'default' => true],
		];
	}
}
