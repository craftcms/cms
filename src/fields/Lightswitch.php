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
 * Lightswitch represents a Lightswitch field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Lightswitch extends Field
{
	// Static
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public static function displayName()
	{
		return Craft::t('app', 'Lightswitch');
	}

	// Properties
	// =========================================================================

	/**
	 * @var boolean Whether the lightswitch should be enabled by default
	 */
	public $default = false;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function getContentColumnType()
	{
		return Schema::TYPE_BOOLEAN;
	}

	/**
	 * @inheritdoc
	 */
	public function getSettingsHtml()
	{
		return Craft::$app->getView()->renderTemplateMacro('_includes/forms', 'lightswitchField', [
			[
				'label' => Craft::t('app', 'Default Value'),
				'id'    => 'default',
				'name'  => 'default',
				'on'    => $this->default,
			]
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function getInputHtml($value, $element)
	{
		// If this is a new entry, look for a default option
		if ($this->isFresh($element))
		{
			$value = $this->default;
		}

		return Craft::$app->getView()->renderTemplate('_includes/forms/lightswitch', [
			'name'  => $this->handle,
			'on'    => (bool) $value,
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function prepareValue($value, $element)
	{
		// It's stored as '0' in the database, but it's returned as false. Change it back to '0'.
		return $value == false ? '0' : $value;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	protected function prepareValueBeforeSave($value, $element)
	{
		return (bool) $value;
	}
}
