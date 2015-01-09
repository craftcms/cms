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
 * PositionSelect fieldtype
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class PositionSelect extends BaseFieldType
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
		return 'Position Select';
	}

	/**
	 * @inheritDoc FieldTypeInterface::defineContentAttribute()
	 *
	 * @return mixed
	 */
	public function defineContentAttribute()
	{
		return AttributeType::String;
	}

	/**
	 * @inheritDoc SavableComponentTypeInterface::getSettingsHtml()
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		return Craft::$app->templates->render('_components/fieldtypes/PositionSelect/settings', [
			'settings'   => $this->getSettings(),
			'allOptions' => array_keys(static::_getOptions()),
		]);
	}

	/**
	 * @inheritDoc SavableComponentTypeInterface::prepSettings()
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
	 * @inheritDoc FieldTypeInterface::getInputHtml()
	 *
	 * @param string $name
	 * @param mixed  $value
	 *
	 * @return string
	 */
	public function getInputHtml($name, $value)
	{
		Craft::$app->templates->includeJsResource('js/PositionSelectInput.js');

		$id = Craft::$app->templates->formatInputId($name);
		Craft::$app->templates->includeJs('new PositionSelectInput("'.Craft::$app->templates->namespaceInputId($id).'");');

		$options = $this->getSettings()->options;

		if (!$value && $options)
		{
			$value = $options[0];
		}

		return Craft::$app->templates->render('_components/fieldtypes/PositionSelect/input', [
			'id'         => $id,
			'name'       => $name,
			'value'      => $value,
			'options'    => $options,
			'allOptions' => $this->_getOptions(),
		]);
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
			'options' => [AttributeType::Mixed, 'default' => array_keys(static::_getOptions())],
		];
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
		return [
			'left'       => Craft::t('Left'),
			'center'     => Craft::t('Center'),
			'right'      => Craft::t('Right'),
			'full'       => Craft::t('Full'),
			'drop-left'  => Craft::t('Drop-left'),
			'drop-right' => Craft::t('Drop-right'),
		];
	}
}
