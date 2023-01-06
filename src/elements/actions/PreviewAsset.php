<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\actions;

use Craft;
use craft\base\ElementAction;

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
        Craft::$app->getView()->registerJsWithVars(fn($type) => <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: $type,
        bulk: false,
        validateSelection: \$selectedItems => \$selectedItems.length === 1,
        activate: \$selectedItems => {
            const \$element = \$selectedItems.find('.element');
            const settings = {};
            if (\$element.data('image-width')) {
                settings.startingWidth = \$element.data('image-width');
                settings.startingHeight = \$element.data('image-height');
            }
            new Craft.PreviewFileModal(\$element.data('id'), Craft.elementIndex.view.elementSelect, settings);
        },
    });
})();
JS, [static::class]);

        return null;
    }
}
