<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\actions;

use craft\base\ElementAction;
use CraftCms\Cms\Support\Facades\AssetRegistry;
use function CraftCms\Cms\t;

/**
 * View represents a View element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class View extends ElementAction
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
            $this->label = t('View');
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
        AssetRegistry::jsWithVars(fn($type) => <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: $type,
        bulk: false,
        validateSelection: (selectedItems, elementIndex) => {
            const \$element = selectedItems.find('.element');
            return (
                \$element.data('url') &&
                (\$element.data('status') === 'enabled' || \$element.data('status') === 'live')
            );
        },
        activate: (selectedItems, elementIndex) => {
            window.open(selectedItems.find('.element').data('url'));
        },
    });
})();
JS, [static::class]);

        return null;
    }
}
