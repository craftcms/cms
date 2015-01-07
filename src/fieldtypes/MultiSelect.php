<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\fieldtypes;

use craft\app\Craft;

/**
 * Class MultiSelect
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class MultiSelect extends BaseOptionsFieldType
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
		return Craft::t('Multi-select');
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

		return Craft::$app->templates->render('_includes/forms/multiselect', array(
			'name'    => $name,
			'values'  => $values,
			'options' => $options
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
		return Craft::t('Multi-select Options');
	}
}
