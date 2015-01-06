<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\elementactions;

use craft\app\enums\AttributeType;
use craft\app\Craft;

/**
 * View Element Action
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class View extends BaseElementAction
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
		return $this->getParams()->label;
	}

	/**
	 * @inheritDoc ElementActionInterface::getTriggerHtml()
	 *
	 * @return string|null
	 */
	public function getTriggerHtml()
	{
		$js = <<<EOT
(function()
{
	var trigger = new Craft.ElementActionTrigger({
		handle: 'View',
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

		craft()->templates->includeJs($js);
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
		return [
			'label' => [AttributeType::String, 'default' => Craft::t('View')],
		];
	}
}
