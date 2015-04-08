<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\elements\actions;

use Craft;
use craft\app\base\ElementAction;
use craft\app\helpers\JsonHelper;

/**
 * Edit represents an Edit element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Edit extends ElementAction
{
	// Properties
	// =========================================================================

	/**
	 * @var string The trigger label
	 */
	public $label;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		if ($this->label === null)
		{
			$this->label = Craft::t('app', 'Edit');
		}
	}

	/**
	 * @inheritdoc
	 */
	public function getTriggerLabel()
	{
		return $this->label;
	}

	/**
	 * @inheritdoc
	 */
	public function getTriggerHtml()
	{
		$type = JsonHelper::encode(static::className());

		$js = <<<EOT
(function()
{
	var trigger = new Craft.ElementActionTrigger({
		type: {$type},
		batch: false,
		validateSelection: function(\$selectedItems)
		{
			return Garnish.hasAttr(\$selectedItems.find('.element'), 'data-editable');
		},
		activate: function(\$selectedItems)
		{
			new Craft.ElementEditor(\$selectedItems.find('.element'));
		}
	});
})();
EOT;

		Craft::$app->getView()->registerJs($js);
	}
}
