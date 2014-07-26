<?php
namespace Craft;

/**
 * Class RadioButtonsFieldType
 *
 * @package craft.app.fieldtypes
 */
class RadioButtonsFieldType extends BaseOptionsFieldType
{
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
	 * Returns the label for the Options setting.
	 *
	 * @return string
	 */
	protected function getOptionsSettingsLabel()
	{
		return Craft::t('Radio Button Options');
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
}
