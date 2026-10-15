<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Actions;

use CraftCms\Cms\Support\Facades\HtmlStack;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class Edit extends ElementAction
{
    public ?string $label = null;

    public function __construct(array|object $config = [])
    {
        parent::__construct($config);

        $this->label ??= t('Edit');
    }

    #[\Override]
    public function getTriggerLabel(): string
    {
        return $this->label;
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
        validateSelection: (selectedItems, elementIndex) => Garnish.hasAttr(selectedItems.find('.element'), 'data-savable'),
        activate: (selectedItems, elementIndex) => {
            const \$element = selectedItems.find('.element:first');
            window.dispatchEvent(new CustomEvent('craft:edit-element', {
                detail: {
                    elementType: \$element.data('type'),
                    settings: {
                        elementId: \$element.data('id'),
                        draftId: \$element.data('draft-id') ?? null,
                        revisionId: \$element.data('revision-id') ?? null,
                        siteId: \$element.data('site-id') ?? null,
                        ownerId: \$element.data('owner-id') ?? null,
                    },
                    cpEditUrl: \$element.data('cp-url') ?? null,
                    trigger: \$element[0],
                },
            }));
        },
    });
})();
JS, [static::class]);

        return null;
    }
}
