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
 * View represents a View element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class PreviewAsset extends ElementAction
{
    // Properties
    // =========================================================================

    /**
     * @var string|null The trigger label
     */
    public $label;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->label === null) {
            $this->label = Craft::t('app', 'Preview file');
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

        $js = <<<EOD
(function()
{
    var trigger = new Craft.ElementActionTrigger({
        type: {$type},
        batch: false,
        validateSelection: function(\$selectedItems)
        {
            var \$element = \$selectedItems.find('.element');

            return \$element.length === 1;
        },
        activate: function(\$selectedItems)
        {
            var settings = {};
            if (\$selectedItems.find('.element').data('image-width')) {
                settings.startingWidth = \$selectedItems.find('.element').data('image-width');
                settings.startingHeight = \$selectedItems.find('.element').data('image-height');
            }
            var modal = new Craft.PreviewFileModal(\$selectedItems.find('.element').data('id'), \$selectedItems, settings);
        }
    });
})();
EOD;

        Craft::$app->getView()->registerJs($js);
    }
}
