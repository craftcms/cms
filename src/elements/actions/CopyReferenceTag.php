<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\elements\actions;

use Craft;
use craft\app\base\ElementAction;
use craft\app\base\ElementInterface;
use craft\app\helpers\JsonHelper;

/**
 * CopyReferenceTag represents a Copy Reference Tag element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class CopyReferenceTag extends ElementAction
{
	// Properties
	// =========================================================================

	/**
	 * @var ElementInterface|string The element type associated with this action
	 */
	public $elementType;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function getTriggerLabel()
	{
		return Craft::t('app', 'Copy reference tag');
	}

	/**
	 * @inheritdoc
	 */
	public function getTriggerHtml()
	{
		$type = JsonHelper::encode(static::className());
		$prompt = JsonHelper::encode(Craft::t('app', '{ctrl}C to copy.'));
		$elementType = $this->elementType;
		$elementTypeHandle = JsonHelper::encode($elementType::classHandle());

		$js = <<<EOT
(function()
{
	var trigger = new Craft.ElementActionTrigger({
		type: {$type},
		batch: false,
		activate: function(\$selectedItems)
		{
			var message = Craft.t({$prompt}, {
				ctrl: (navigator.appVersion.indexOf('Mac') ? '⌘' : 'Ctrl-')
			});

			prompt(message, '{'+{$elementTypeHandle}+':'+\$selectedItems.find('.element').data('id')+'}');
		}
	});
})();
EOT;

		Craft::$app->getView()->registerJs($js);
	}
}
