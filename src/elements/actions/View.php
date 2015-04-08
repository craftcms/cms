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
 * View represents a View element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class View extends ElementAction
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
			$this->label = Craft::t('app', 'View');
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
			var \$element = \$selectedItems.find('.element');

			return (
				\$element.data('url') &&
				(\$element.data('status') == 'enabled' || \$element.data('status') == 'live')
			);
		},
		activate: function(\$selectedItems)
		{
			window.open(\$selectedItems.find('.element').data('url'));
		}
	});
})();
EOT;

		Craft::$app->getView()->registerJs($js);
	}
}
