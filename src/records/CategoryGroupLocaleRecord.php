<?php
namespace Craft;

/**
 * Class CategoryGroupLocaleRecord
 *
 * @package craft.app.records
 */
class CategoryGroupLocaleRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'categorygroups_i18n';
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'locale'          => array(AttributeType::Locale, 'required' => true),
			'urlFormat'       => AttributeType::UrlFormat,
			'nestedUrlFormat' => AttributeType::UrlFormat,
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'group'  => array(static::BELONGS_TO, 'CategoryGroupRecord', 'required' => true, 'onDelete' => static::CASCADE),
			'locale' => array(static::BELONGS_TO, 'LocaleRecord', 'locale', 'required' => true, 'onDelete' => static::CASCADE, 'onUpdate' => static::CASCADE),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('groupId', 'locale'), 'unique' => true),
		);
	}
}
