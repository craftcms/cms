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
 * NewChild represents a New Child element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class NewChild extends ElementAction
{
	// Properties
	// =========================================================================

	/**
	 * @var string The trigger label
	 */
	public $label;

	/**
	 * @var integer The maximum number of levels that the structure is allowed to have
	 */
	public $maxLevels;

	/**
	 * @var string The URL that the user should be taken to after clicking on this element action
	 */
	public $newChildUrl;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		if ($this->label === null)
		{
			$this->label = Craft::t('app', 'New Child');
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
		$maxLevels = JsonHelper::encode($this->maxLevels);
		$newChildUrl = JsonHelper::encode($this->newChildUrl);

		$js = <<<EOT
(function()
{
	var trigger = new Craft.ElementActionTrigger({
		type: {$type},
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

		Craft::$app->getView()->registerJs($js);
	}
}
