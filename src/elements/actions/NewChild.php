<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\actions;

use Craft;
use craft\base\ElementAction;
use craft\helpers\Json;

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
     * @var string|null The trigger label
     */
    public $label;

    /**
     * @var int|null The maximum number of levels that the structure is allowed to have
     */
    public $maxLevels;

    /**
     * @var string|null The URL that the user should be taken to after clicking on this element action
     */
    public $newChildUrl;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->label === null) {
            $this->label = Craft::t('app', 'New child');
        }
    }

    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return $this->label;
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml()
    {
        $type = Json::encode(static::class);
        $maxLevels = Json::encode($this->maxLevels);
        $newChildUrl = Json::encode($this->newChildUrl);

        $js = <<<EOD
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

    if (Craft.elementIndex.view.structureTableSort)
    {
        Craft.elementIndex.view.structureTableSort.on('positionChange', $.proxy(trigger, 'updateTrigger'));
    }
})();
EOD;

        Craft::$app->getView()->registerJs($js);
    }
}
