<?php
namespace Blocks;

/**
 *
 */
class SingletonLocaleRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'singletons_i18n';
	}

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'locale'    => array(AttributeType::Locale, 'required' => true),
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'singleton' => array(static::BELONGS_TO, 'SingletonRecord', 'required' => true, 'onDelete' => static::CASCADE),
			'locale'    => array(static::BELONGS_TO, 'LocaleRecord', 'locale', 'required' => true, 'onDelete' => static::CASCADE, 'onUpdate' => static::CASCADE),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('singletonId', 'locale'), 'unique' => true),
		);
	}
}
