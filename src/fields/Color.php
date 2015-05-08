<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\fields;

use Craft;
use craft\app\base\Field;
use craft\app\helpers\HtmlHelper;
use yii\db\Schema;

/**
 * Color represents a Color field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Color extends Field
{
	// Static
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public static function displayName()
	{
		return Craft::t('app', 'Color');
	}

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function getContentColumnType()
	{
		return Schema::TYPE_STRING.'(7)';
	}

	/**
	 * @inheritdoc
	 */
	public function getInputHtml($value, $element)
	{
		// Default to black, so the JS-based color picker is consistent with Chrome
		if (!$value)
		{
			$value = '#000000';
		}

		return Craft::$app->getView()->renderTemplate('_includes/forms/color', [
			'id'    => Craft::$app->getView()->formatInputId($this->handle),
			'name'  => $this->handle,
			'value' => $value,
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function getStaticHtml($value, $element)
	{
		if ($value)
		{
			return HtmlHelper::encodeParams('<div class="color" style="cursor: default;"><div class="colorpreview" style="background-color: {bgColor};"></div></div><div class="colorhex">{bgColor}</div>', [
				'bgColor' => $value
			]);
		}
	}
}
