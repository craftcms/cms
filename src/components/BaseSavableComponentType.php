<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\components;

use craft\app\base\Model;
use craft\app\models\Params as ParamsModel;

/**
 * Base savable component class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class BaseSavableComponentType extends BaseComponentType implements SavableComponentTypeInterface
{
	// Properties
	// =========================================================================

	/**
	 * The model instance associated with the current component instance.
	 *
	 * @var Model
	 */
	public $model;

	/**
	 * @var
	 */
	private $_settings;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc SavableComponentTypeInterface::getSettings()
	 *
	 * @return Model
	 */
	public function getSettings()
	{
		if (!isset($this->_settings))
		{
			$this->_settings = $this->getSettingsModel();
		}

		return $this->_settings;
	}

	/**
	 * @inheritDoc SavableComponentTypeInterface::setSettings()
	 *
	 * @param array|Model $values
	 *
	 * @return null
	 */
	public function setSettings($values)
	{
		if ($values)
		{
			if ($values instanceof Model)
			{
				$this->_settings = $values;
			}
			else
			{
				$this->getSettings()->setAttributes($values);
			}
		}
	}

	/**
	 * @inheritDoc SavableComponentTypeInterface::getSettingsHtml()
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		return null;
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
		return $settings;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * Returns the settings model.
	 *
	 * @return Model
	 */
	protected function getSettingsModel()
	{
		return new ParamsModel($this->defineSettings());
	}

	/**
	 * Defines the settings.
	 *
	 * @return array
	 */
	protected function defineSettings()
	{
		return [];
	}
}
