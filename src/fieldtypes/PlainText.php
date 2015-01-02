<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\fieldtypes;

use craft\app\Craft;

/**
 * PlainText fieldtype
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class PlainText extends BaseFieldType
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc ComponentTypeInterface::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Plain Text');
	}

	/**
	 * @inheritDoc SavableComponentTypeInterface::getSettingsHtml()
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
	 * @inheritDoc FieldTypeInterface::defineContentAttribute()
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
	 * @inheritDoc FieldTypeInterface::getInputHtml()
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
