<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\fields;

use Craft;

/**
 * Dropdown represents a Dropdown field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Dropdown extends BaseOptionsField
{
	// Static
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public static function displayName()
	{
		return Craft::t('app', 'Dropdown');
	}

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function getInputHtml($name, $value)
	{
		$options = $this->getTranslatedOptions();

		// If this is a new entry, look for a default option
		if ($this->isFresh())
		{
			$value = $this->getDefaultValue();
		}

		return Craft::$app->templates->render('_includes/forms/select', [
			'name'    => $name,
			'value'   => $value,
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
		return Craft::t('app', 'Dropdown Options');
	}
}
