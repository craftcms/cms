<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\fieldtypes;

use craft\app\Craft;

/**
 * Checkboxes fieldtype
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Checkboxes extends BaseOptionsFieldType
{
	// Properties
	// =========================================================================

	/**
	 * @var bool
	 */
	protected $multi = true;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc ComponentTypeInterface::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Checkboxes');
	}

	/**
	 * @inheritDoc FieldTypeInterface::getInputHtml()
	 *
	 * @param string $name
	 * @param mixed  $values
	 *
	 * @return string
	 */
	public function getInputHtml($name, $values)
	{
		$options = $this->getTranslatedOptions();

		// If this is a new entry, look for any default options
		if ($this->isFresh())
		{
			$values = $this->getDefaultValue();
		}

		return craft()->templates->render('_includes/forms/checkboxGroup', array(
			'name'    => $name,
			'options' => $options,
			'values'  => $values
		));
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseOptionsFieldType::getOptionsSettingsLabel()
	 *
	 * @return string
	 */
	protected function getOptionsSettingsLabel()
	{
		return Craft::t('Checkbox Options');
	}
}
