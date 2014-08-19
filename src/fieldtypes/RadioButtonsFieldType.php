<?php
namespace Craft;

/**
 * Class RadioButtonsFieldType
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.fieldtypes
 * @since     1.0
 */
class RadioButtonsFieldType extends BaseOptionsFieldType
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns the type of field this is.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Radio Buttons');
	}

	/**
	 * Returns the field's input HTML.
	 *
	 * @param string $name
	 * @param mixed  $value
	 *
	 * @return string
	 */
	public function getInputHtml($name, $value)
	{
		$options = $this->getTranslatedOptions();

		// If this is a new entry, look for a default option
		if ($this->isFresh())
		{
			foreach ($options as $option)
			{
				if (!empty($option['default']))
				{
					$value = $option['value'];
					break;
				}
			}
		}

		return craft()->templates->render('_includes/forms/radioGroup', array(
			'name'    => $name,
			'value'   => $value,
			'options' => $options
		));
	}

	// Protected Methods
	// =========================================================================

	/**
	 * Returns the label for the Options setting.
	 *
	 * @return string
	 */
	protected function getOptionsSettingsLabel()
	{
		return Craft::t('Radio Button Options');
	}
}
