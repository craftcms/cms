<?php
namespace Craft;

/**
 * Class LightswitchFieldType
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.fieldtypes
 * @since     1.3
 */
class LightswitchFieldType extends BaseFieldType
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc IComponentType::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Lightswitch');
	}

	/**
	 * @inheritDoc IFieldType::defineContentAttribute()
	 *
	 * @return mixed
	 */
	public function defineContentAttribute()
	{
		return AttributeType::Bool;
	}

	/**
	 * @inheritDoc ISavableComponentType::getSettingsHtml()
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		return craft()->templates->renderMacro('_includes/forms', 'lightswitchField', array(
			array(
				'label' => Craft::t('Default Value'),
				'id'    => 'default',
				'name'  => 'default',
				'on'    => $this->getSettings()->default,
			)
		));
	}

	/**
	 * @inheritDoc IFieldType::getInputHtml()
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

		return craft()->templates->render('_includes/forms/lightswitch', array(
			'name'  => $name,
			'on'    => (bool) $value,
		));
	}

	/**
	 * @inheritDoc IFieldType::prepValueFromPost()
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
	 * @inheritDoc IFieldType::prepValue()
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
		return array(
			'default' => array(AttributeType::Bool, 'default' => false),
		);
	}
}
