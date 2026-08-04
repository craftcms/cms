<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Actions;

use CraftCms\Cms\Element\Actions\ElementAction;
use CraftCms\Cms\Support\Facades\HtmlStack;

use function CraftCms\Cms\t;

class CopyUrl extends ElementAction
{
    #[\Override]
    public function getTriggerLabel(): string
    {
        return t('Copy URL');
    }

    #[\Override]
    public static function supportsBulk(): bool
    {
        return false;
    }

    public function getTriggerHtml(): ?string
    {
        HtmlStack::jsWithVars(fn ($type) => <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: $type,
        bulk: false,
        validateSelection: (selectedItems, elementIndex) => !!selectedItems.find('.element').data('url'),
        activate: (selectedItems, elementIndex) => {
            Craft.ui.createCopyTextPrompt({
                label: Craft.t('app', 'Copy the URL'),
                value: selectedItems.find('.element').data('url'),
            });
        },
    })
})();
JS, [static::class]);

        return null;
    }
}
