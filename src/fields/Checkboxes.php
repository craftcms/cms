<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\fields;

use Craft;

/**
 * Checkboxes represents a Checkboxes field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Checkboxes extends BaseOptionsField
{
	// Static
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public static function displayName()
	{
		return Craft::t('app', 'Checkboxes');
	}

	// Properties
	// =========================================================================

	/**
	 * @var bool
	 */
	protected $multi = true;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function getInputHtml($name, $values)
	{
		$options = $this->getTranslatedOptions();

		// If this is a new entry, look for any default options
		if ($this->isFresh())
		{
			$values = $this->getDefaultValue();
		}

		return Craft::$app->templates->render('_includes/forms/checkboxGroup', [
			'name'    => $name,
			'values'  => $values,
			'options' => $options
		]);
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	protected function getOptionsSettingsLabel()
	{
		return Craft::t('app', 'Checkbox Options');
	}
}
