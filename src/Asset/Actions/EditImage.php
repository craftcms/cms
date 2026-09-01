<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Actions;

use CraftCms\Cms\Element\Actions\ElementAction;
use CraftCms\Cms\Support\Facades\HtmlStack;

use function CraftCms\Cms\t;

class EditImage extends ElementAction
{
    public string $label;

    /** @param array<string, mixed>|object $config */
    public function __construct(array|object $config = [])
    {
        parent::__construct($config);

        $this->label ??= t('Edit Image');
    }

    #[\Override]
    public static function supportsBulk(): bool
    {
        return false;
    }

    #[\Override]
    public function getTriggerLabel(): string
    {
        return $this->label;
    }

    public function getTriggerHtml(): ?string
    {
        HtmlStack::jsWithVars(fn ($type) => <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: $type,
        bulk: false,
        validateSelection: (selectedItems, elementIndex) => Garnish.hasAttr(selectedItems.find('.element'), 'data-editable-image'),
        activate: (selectedItems, elementIndex) => {
            const \$element = selectedItems.find('.element:first');
            new Craft.AssetImageEditor(\$element.data('id'));
        },
    });
})();
JS, [static::class]);

        return null;
    }
}
