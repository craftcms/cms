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
		return Craft::$app->templates->renderMacro('_includes/forms', 'lightswitchField', [
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
	public function getInputHtml($name, $value)
	{
		// If this is a new entry, look for a default option
		if ($this->isFresh())
		{
			$value = $this->default;
		}

		return Craft::$app->templates->render('_includes/forms/lightswitch', [
			'name'  => $name,
			'on'    => (bool) $value,
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function prepValueFromPost($value)
	{
		return (bool) $value;
	}

	/**
	 * @inheritdoc
	 */
	public function prepValue($value)
	{
		// It's stored as '0' in the database, but it's returned as false. Change it back to '0'.
		return $value == false ? '0' : $value;
	}
}
