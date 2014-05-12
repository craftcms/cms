<?php
namespace Craft;

/**
 *
 */
class MultiSelectFieldType extends BaseOptionsFieldType
{
	protected $multi = true;

	/**
	 * Returns the type of field this is.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Multi-select');
	}

	/**
	 * Returns the label for the Options setting.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getOptionsSettingsLabel()
	{
		return Craft::t('Multi-select Options');
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
		$options = $this->getTranslatedOptions();

		// If this is a new entry, look for any default options
		if ($this->isFresh())
		{
			$values = array();

			foreach ($options as $option)
			{
				if (!empty($option['default']))
				{
					$values[] = $option['value'];
				}
			}
		}

		return craft()->templates->render('_includes/forms/multiselect', array(
			'name'    => $name,
			'values'  => $values,
			'options' => $options
		));
	}
}
