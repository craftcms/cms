<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
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
		return Craft::t('app', 'Position Select');
	}

	/**
	 * @inheritdoc
	 */
	public static function populateModel($model, $config)
	{
		if (isset($config['options']))
		{
			$config['options'] = array_filter($config['options']);
		}

		parent::populateModel($model, $config);
	}

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
		return Craft::$app->getView()->renderTemplate('_components/fieldtypes/PositionSelect/settings', [
			'field'      => $this,
			'allOptions' => array_keys(static::_getOptions()),
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function getInputHtml($value, $element)
	{
		if (empty($this->options))
		{
			return '<p><em>'.Craft::t('app', 'No options selected.').'</em></p>';
		}

		Craft::$app->getView()->registerJsResource('js/PositionSelectInput.js');

		$id = Craft::$app->getView()->formatInputId($this->handle);
		Craft::$app->getView()->registerJs('new PositionSelectInput("'.Craft::$app->getView()->namespaceInputId($id).'");');

		if (!$value && $this->options)
		{
			$value = $this->options[0];
		}

		return Craft::$app->getView()->renderTemplate('_components/fieldtypes/PositionSelect/input', [
			'id'         => $id,
			'name'       => $this->handle,
			'value'      => $value,
			'options'    => $this->options,
			'allOptions' => $this->_getOptions(),
		]);
	}
}
