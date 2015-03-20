<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\fields;
use Craft;
use craft\app\base\Field;
use yii\db\Schema;

/**
 * PositionSelect represents a Position Select field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class PositionSelect extends Field
{
	// Static
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public static function displayName()
	{
		return 'Position Select';
	}

	// Properties
	// =========================================================================

	/**
	 * @var string[] The position options that should be shown in the field
	 */
	public $options;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		if ($this->options === null)
		{
			$this->options = array_keys(static::_getOptions());
		}
	}

	/**
	 * @inheritdoc
	 */
	public function getContentColumnType()
	{
		return Schema::TYPE_STRING.'(100)';
	}

	/**
	 * @inheritdoc
	 */
	public function getSettingsHtml()
	{
		return Craft::$app->templates->render('_components/fieldtypes/PositionSelect/settings', [
			'field'      => $this,
			'allOptions' => array_keys(static::_getOptions()),
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function prepSettings($settings)
	{
		$settings['options'] = array_keys(array_filter($settings['options']));
		return $settings;
	}

	/**
	 * @inheritdoc
	 */
	public function getInputHtml($value, $element)
	{
		Craft::$app->templates->includeJsResource('js/PositionSelectInput.js');

		$id = Craft::$app->templates->formatInputId($this->handle);
		Craft::$app->templates->includeJs('new PositionSelectInput("'.Craft::$app->templates->namespaceInputId($id).'");');

		if (!$value && $this->options)
		{
			$value = $this->options[0];
		}

		return Craft::$app->templates->render('_components/fieldtypes/PositionSelect/input', [
			'id'         => $id,
			'name'       => $this->handle,
			'value'      => $value,
			'options'    => $this->options,
			'allOptions' => $this->_getOptions(),
		]);
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
			'left'       => Craft::t('app', 'Left'),
			'center'     => Craft::t('app', 'Center'),
			'right'      => Craft::t('app', 'Right'),
			'full'       => Craft::t('app', 'Full'),
			'drop-left'  => Craft::t('app', 'Drop-left'),
			'drop-right' => Craft::t('app', 'Drop-right'),
		];
	}
}
