<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Actions;

use CraftCms\Cms\Support\Facades\HtmlStack;

use function CraftCms\Cms\t;

class View extends ElementAction
{
    public ?string $label = null;

    public function __construct(array|object $config = [])
    {
        parent::__construct($config);

        $this->label ??= t('View');
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
