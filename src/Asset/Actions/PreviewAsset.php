<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Actions;

use CraftCms\Cms\Element\Actions\ElementAction;
use CraftCms\Cms\Support\Facades\HtmlStack;

use function CraftCms\Cms\t;

class PreviewAsset extends ElementAction
{
    public ?string $label = null;

    public function __construct(array|object $config = [])
    {
        parent::__construct($config);

        $this->label ??= t('Preview file');
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
        validateSelection: (selectedItems, elementIndex) => selectedItems.length === 1,
        activate: (selectedItems, elementIndex) => {
            const \$element = selectedItems.find('.element');
            const settings = {};
            if (\$element.data('image-width')) {
                settings.startingWidth = \$element.data('image-width');
                settings.startingHeight = \$element.data('image-height');
            }
            new Craft.PreviewFileModal(\$element.data('id'), elementIndex.view.elementSelect, settings);
        },
    });
})();
JS, [static::class]);

        return null;
    }
}
