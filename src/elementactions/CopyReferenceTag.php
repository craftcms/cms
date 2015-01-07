<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\elementactions;

use craft\app\Craft;
use craft\app\enums\AttributeType;
use craft\app\helpers\JsonHelper;

/**
 * Copy Reference Tag Element Action
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class CopyReferenceTag extends BaseElementAction
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc ComponentTypeInterface::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Copy reference tag');
	}

	/**
	 * @inheritDoc ElementActionInterface::getTriggerHtml()
	 *
	 * @return string|null
	 */
	public function getTriggerHtml()
	{
		$prompt = JsonHelper::encode(Craft::t('{ctrl}C to copy.'));
		$elementType = lcfirst($this->getParams()->elementType);

		$js = <<<EOT
(function()
{
	var trigger = new Craft.ElementActionTrigger({
		handle: 'CopyReferenceTag',
		batch: false,
		activate: function(\$selectedItems)
		{
			var message = Craft.t({$prompt}, {
				ctrl: (navigator.appVersion.indexOf('Mac') ? '⌘' : 'Ctrl-')
			});

			prompt(message, '{{$elementType}:'+\$selectedItems.find('.element').data('id')+'}');
		}
	});
})();
EOT;

		Craft::$app->templates->includeJs($js);
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseElementAction::defineParams()
	 *
	 * @return array
	 */
	protected function defineParams()
	{
		return array(
			'elementType' => AttributeType::String,
		);
	}
}
