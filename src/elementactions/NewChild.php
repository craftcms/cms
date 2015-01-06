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
 * New Child Element Action
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class NewChild extends BaseElementAction
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
		$maxLevels = JsonHelper::encode($this->getParams()->maxLevels);
		$newChildUrl = JsonHelper::encode($this->getParams()->newChildUrl);

		$js = <<<EOT
(function()
{
	var trigger = new Craft.ElementActionTrigger({
		handle: 'NewChild',
		batch: false,
		validateSelection: function(\$selectedItems)
		{
			return (!$maxLevels || $maxLevels > \$selectedItems.find('.element').data('level'));
		},
		activate: function(\$selectedItems)
		{
			Craft.redirectTo(Craft.getUrl($newChildUrl, 'parentId='+\$selectedItems.find('.element').data('id')));
		}
	});

	if (Craft.elementIndex.structureTableSort)
	{
		Craft.elementIndex.structureTableSort.on('positionChange', $.proxy(trigger, 'updateTrigger'));
	}
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
			'label'       => [AttributeType::String, 'default' => Craft::t('New Child')],
			'maxLevels'   => AttributeType::Number,
			'newChildUrl' => AttributeType::String,
		];
	}
}
