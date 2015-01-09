<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\fieldtypes;

use Craft;
use craft\app\enums\AttributeType;

/**
 * Lightswitch fieldtype
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Lightswitch extends BaseFieldType
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
		return Craft::t('Lightswitch');
	}

	/**
	 * @inheritDoc FieldTypeInterface::defineContentAttribute()
	 *
	 * @return mixed
	 */
	public function defineContentAttribute()
	{
		return AttributeType::Bool;
	}

	/**
	 * @inheritDoc SavableComponentTypeInterface::getSettingsHtml()
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		return Craft::$app->templates->renderMacro('_includes/forms', 'lightswitchField', [
			[
				'label' => Craft::t('Default Value'),
				'id'    => 'default',
				'name'  => 'default',
				'on'    => $this->getSettings()->default,
			]
		]);
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
		// If this is a new entry, look for a default option
		if ($this->isFresh())
		{
			$value = $this->getSettings()->default;
		}

		return Craft::$app->templates->render('_includes/forms/lightswitch', [
			'name'  => $name,
			'on'    => (bool) $value,
		]);
	}

	/**
	 * @inheritDoc FieldTypeInterface::prepValueFromPost()
	 *
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	public function prepValueFromPost($value)
	{
		return (bool) $value;
	}

	/**
	 * @inheritDoc FieldTypeInterface::prepValue()
	 *
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	public function prepValue($value)
	{
		// It's stored as '0' in the database, but it's returned as false. Change it back to '0'.
		return $value == false ? '0' : $value;
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
		return [
			'default' => [AttributeType::Bool, 'default' => false],
		];
	}
}
