<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Actions;

use CraftCms\Cms\Element\Actions\ElementAction;
use CraftCms\Cms\Support\Facades\HtmlStack;

use function CraftCms\Cms\t;

class NewSiblingBefore extends ElementAction
{
    /**
     * @var string|null The trigger label
     */
    public ?string $label = null;

    /**
     * @var string|null The URL that the user should be taken to after clicking on this element action
     */
    public ?string $newSiblingUrl = null;

    #[\Override]
    public function setElementType(string $elementType): void
    {
        parent::setElementType($elementType);

        if (! isset($this->label)) {
            $this->label = t('Create a new {type} before', [
                'type' => $elementType::lowerDisplayName(),
            ]);
        }
    }

    #[\Override]
    public function getTriggerLabel(): string
    {
        return $this->label;
    }

    public function getTriggerHtml(): ?string
    {
        HtmlStack::jsWithVars(fn ($type, $newSiblingUrl) => <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: $type,
        bulk: false,
        activate: (selectedItems, elementIndex) => {
            Craft.redirectTo(Craft.getUrl($newSiblingUrl, 'before=' + selectedItems.find('.element').data('id')));
        },
    });
})();
JS, [static::class, $this->newSiblingUrl]);

        return null;
    }
}
