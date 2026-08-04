<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Actions;

use CraftCms\Cms\Support\Facades\HtmlStack;

use function CraftCms\Cms\t;

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
            Craft.createElementEditor(\$element.data('type'), \$element);
        },
    });
})();
JS, [static::class]);

        return null;
    }
}
