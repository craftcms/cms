<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\fields;

use Craft;
use craft\app\base\Field;
use craft\app\helpers\DbHelper;
use yii\db\Schema;

/**
 * PlainText represents a Plain Text field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class PlainText extends Field
{
	// Static
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public static function displayName()
	{
		return Craft::t('app', 'Plain Text');
	}

	// Properties
	// =========================================================================

	/**
	 * @var string The input’s placeholder text
	 */
	public $placeholder;

	/**
	 * @var boolean Whether the input should allow line breaks
	 */
	public $multiline;

	/**
	 * @var integer The minimum number of rows the input should have, if multi-line
	 */
	public $initialRows = 4;

	/**
	 * @var integer The maximum number of characters allowed in the field
	 */
	public $maxLength;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		$rules = parent::rules();
		$rules[] = [['initialRows', 'maxLength'], 'integer', 'min' => 1];
		return $rules;
	}

	/**
	 * @inheritdoc
	 */
	public function getSettingsHtml()
	{
		return Craft::$app->getView()->renderTemplate('_components/fieldtypes/PlainText/settings', [
			'field' => $this
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function getContentColumnType()
	{
		if (!$this->maxLength)
		{
			return Schema::TYPE_TEXT;
		}
		else
		{
			return DbHelper::getTextualColumnTypeByContentLength($this->maxLength);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function getInputHtml($value, $element)
	{
		return Craft::$app->getView()->renderTemplate('_components/fieldtypes/PlainText/input', [
			'name'  => $this->handle,
			'value' => $value,
			'field' => $this,
		]);
	}
}
