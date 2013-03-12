<?php
namespace Craft;

/**
 *
 */
class CheckboxesFieldType extends BaseOptionsFieldType
{
	protected $multi = true;

	/**
	 * Returns the type of field this is.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Checkboxes');
	}

	/**
	 * Returns the label for the Options setting.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getOptionsSettingsLabel()
	{
		return Craft::t('Checkbox Options');
	}

	/**
	 * Returns the field's input HTML.
	 *
	 * @param string $name
	 * @param mixed  $values
	 * @return string
	 */
	public function getInputHtml($name, $values)
	{
		return craft()->templates->render('_components/fieldtypes/Checkboxes/input', array(
			'name'     => $name,
			'values'   => $values,
			'settings' => $this->getSettings()
		));
	}
}
