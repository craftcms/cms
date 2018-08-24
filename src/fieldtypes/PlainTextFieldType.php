<?php
namespace Craft;

use LitEmoji\LitEmoji;

/**
 * Class PlainTextFieldType
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.fieldtypes
 * @since     1.0
 */
class PlainTextFieldType extends BaseFieldType implements IPreviewableFieldType
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc IComponentType::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Plain Text');
	}

	/**
	 * @inheritDoc ISavableComponentType::getSettingsHtml()
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
	 * @inheritDoc IFieldType::defineContentAttribute()
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
		else
		{
			$columnType = DbHelper::getTextualColumnTypeByContentLength($maxLength);
		}

		return array(AttributeType::String, 'column' => $columnType, 'maxLength' => $maxLength);
	}

	/**
	 * @inheritDoc IFieldType::prepValue()
	 *
	 * @param mixed $value The field’s stored value.
	 *
	 * @return mixed The prepped value.
	 */
	public function prepValue($value)
	{
		if (PHP_VERSION_ID >= 50600)
		{
			$value = LitEmoji::shortcodeToUnicode($value);
		}

		return $value;
	}

	/**
	 * @inheritDoc IFieldType::getInputHtml()
	 *
	 * @param string $name
	 * @param mixed  $value
	 *
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

	/**
	 * @inheritDoc IFieldType::prepValueFromPost()
	 *
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	public function prepValueFromPost($value)
	{
		if (PHP_VERSION_ID >= 50600)
		{
			$value = LitEmoji::unicodeToShortcode($value);
		}

		return $value;
	}

	/**
	 * @inheritDoc IFieldType::getSearchKeywords()
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	public function getSearchKeywords($value)
	{
		$value = (string) $value;

		if (PHP_VERSION_ID >= 50600)
		{
			$value = LitEmoji::unicodeToShortcode($value);
		}

		return $value;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseSavableComponentType::defineSettings()
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
}
