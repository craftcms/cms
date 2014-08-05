<?php
namespace Craft;

/**
 * Class DropdownFieldType
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.etc.fieldtypes
 * @since     1.0
 */
class DropdownFieldType extends BaseOptionsFieldType
{
	////////////////////
	// PUBLIC METHODS
	////////////////////

	/**
	 * Returns the type of field this is.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Dropdown');
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

		return craft()->templates->render('_includes/forms/select', array(
			'name'    => $name,
			'value'   => $value,
			'options' => $options
		));
	}

	////////////////////
	// PROTECTED METHODS
	////////////////////

	/**
	 * Returns the label for the Options setting.
	 *
	 * @return string
	 */
	protected function getOptionsSettingsLabel()
	{
		return Craft::t('Dropdown Options');
	}
}
