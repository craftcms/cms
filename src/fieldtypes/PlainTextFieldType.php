<?php
namespace Craft;

/**
 * Class PlainTextFieldType
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 * @package   craft.app.etc.fieldtypes
 * @since     1.0
 */
class PlainTextFieldType extends BaseFieldType
{
	/**
	 * Returns the type of field this is.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Plain Text');
	}

	/**
	 * Defines the settings.
	 *
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'placeholder'   => array(AttributeType::String),
			'multiline'     => array(AttributeType::Bool),
			'initialRows'   => array(AttributeType::Number, 'min' => 1, 'default' => 4),
			'maxLength'     => array(AttributeType::Number, 'min' => 0),
		);
	}

	/**
	 * Returns the field's settings HTML.
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		return craft()->templates->render('_components/fieldtypes/PlainText/settings', array(
			'settings' => $this->getSettings()
		));
	}

	/**
	 * Returns the content attribute config.
	 *
	 * @return mixed
	 */
	public function defineContentAttribute()
	{
		$maxLength = $this->getSettings()->maxLength;

		if (!$maxLength)
		{
			$columnType = ColumnType::Text;
		}
		// TODO: MySQL specific
		else if ($maxLength <= 255)
		{
			$columnType = ColumnType::Varchar;
		}
		else if ($maxLength <= 65535)
		{
			$columnType = ColumnType::Text;
		}
		else if ($maxLength <= 16777215)
		{
			$columnType = ColumnType::MediumText;
		}
		else
		{
			$columnType = ColumnType::LongText;
		}

		return array(AttributeType::String, 'column' => $columnType, 'maxLength' => $maxLength);
	}

	/**
	 * Returns the field's input HTML.
	 *
	 * @param string $name
	 * @param mixed  $value
	 * @return string
	 */
	public function getInputHtml($name, $value)
	{
		return craft()->templates->render('_components/fieldtypes/PlainText/input', array(
			'name'     => $name,
			'value'    => $value,
			'settings' => $this->getSettings()
		));
	}
}
