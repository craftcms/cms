<?php
namespace Craft;

/**
 * Class PositionSelectFieldType
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.fieldtypes
 * @since     1.0
 */
class PositionSelectFieldType extends BaseFieldType
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns the type of field this is.
	 *
	 * @return string
	 */
	public function getName()
	{
		return 'Position Select';
	}

	/**
	 * Returns the content attribute config.
	 *
	 * @return mixed
	 */
	public function defineContentAttribute()
	{
		return AttributeType::String;
	}

	/**
	 * Returns the field's settings HTML.
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		return craft()->templates->render('_components/fieldtypes/PositionSelect/settings', array(
			'settings'   => $this->getSettings(),
			'allOptions' => static::_getOptions(),
		));
	}

	/**
	 * Preps the settings before they're saved to the database.
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public function prepSettings($settings)
	{
		$settings['options'] = array_keys(array_filter($settings['options']));
		return $settings;
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
		craft()->templates->includeJsResource('js/PositionSelectInput.js');

		$id = craft()->templates->formatInputId($name);
		craft()->templates->includeJs('new PositionSelectInput("'.craft()->templates->namespaceInputId($id).'");');

		$options = $this->getSettings()->options;

		if (!$value && $options)
		{
			$value = $options[0];
		}

		return craft()->templates->render('_components/fieldtypes/PositionSelect/input', array(
			'id'      => $id,
			'name'    => $name,
			'value'   => $value,
			'options' => $options,
		));
	}

	// Protected Methods
	// =========================================================================

	/**
	 * Defines the settings.
	 *
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'options' => array(AttributeType::Mixed, 'default' => static::_getOptions()),
		);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns the position options.
	 *
	 * @return array
	 */
	private static function _getOptions()
	{
		return array('left', 'center', 'right', 'full', 'drop-left', 'drop-right');
	}
}
