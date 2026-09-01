<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Actions;

use CraftCms\Cms\Element\Actions\ElementAction;
use CraftCms\Cms\Support\Facades\HtmlStack;
use RuntimeException;

use function CraftCms\Cms\t;

class CopyReferenceTag extends ElementAction
{
    #[\Override]
    public function getTriggerLabel(): string
    {
        return t('Copy reference tag');
    }

    #[\Override]
    public static function supportsBulk(): bool
    {
        return false;
    }

    public function getTriggerHtml(): ?string
    {
        $refHandle = $this->elementType::refHandle();
        if ($refHandle === null) {
            throw new RuntimeException("Element type \"$this->elementType\" doesn't have a reference handle.");
        }

        HtmlStack::jsWithVars(fn ($type, $refHandle) => <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: $type,
        bulk: false,
        activate: (selectedItems, elementIndex) => {
            Craft.ui.createCopyTextPrompt({
                label: Craft.t('app', 'Copy the reference tag'),
                value: '{' + $refHandle + ':' + selectedItems.find('.element').data('id') + '}',
            });
        },
    })
})();
JS, [static::class, $refHandle]);

        return null;
    }
}
