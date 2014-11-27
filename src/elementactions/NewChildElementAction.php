<?php
namespace Craft;

/**
 * New Child Element Action
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 * @package   craft.app.elementactions
 * @since     2.3
 */
class NewChildElementAction extends BaseElementAction
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc IComponentType::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->getParams()->label;
	}

	/**
	 * @inheritDoc IElementAction::getTriggerHtml()
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
		return array(
			'label'       => array(AttributeType::String, 'default' => Craft::t('New Child')),
			'maxLevels'   => AttributeType::Number,
			'newChildUrl' => AttributeType::String,
		);
	}
}
