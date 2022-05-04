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
 * @since 3.0.0
 */
class PreviewAsset extends ElementAction
{
    /**
     * @var string|null The trigger label
     */
    public ?string $label = null;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        if (!isset($this->label)) {
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
    public function getTriggerHtml(): ?string
    {
        $type = Json::encode(static::class);

        $js = <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: $type,
        batch: false,
        validateSelection: function(\$selectedItems)
        {
            return \$selectedItems.length === 1;
        },
        activate: function(\$selectedItems)
        {
            var settings = {};
            if (\$selectedItems.find('.element').data('image-width')) {
                settings.startingWidth = \$selectedItems.find('.element').data('image-width');
                settings.startingHeight = \$selectedItems.find('.element').data('image-height');
            }
            var modal = new Craft.PreviewFileModal(\$selectedItems.find('.element').data('id'), Craft.elementIndex.view.elementSelect, settings);
        }
    });
})();
JS;

        Craft::$app->getView()->registerJs($js);
        return null;
    }
}
