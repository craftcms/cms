<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Actions;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\HtmlStack;
use Illuminate\Support\Facades\Gate;

use function CraftCms\Cms\t;

class DeleteForSite extends ElementAction
{
    /**
     * @var string|null The confirmation message that should be shown before the elements get deleted
     */
    public ?string $confirmationMessage = null;

    /**
     * @var string|null The message that should be shown after the elements get deleted
     */
    public ?string $successMessage = null;

    public function getTriggerHtml(): ?string
    {
        // Only enable for deletable elements, per canDelete()
        HtmlStack::jsWithVars(fn ($type) => <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: $type,
        validateSelection: (selectedItems, elementIndex) => {
            for (let i = 0; i < selectedItems.length; i++) {
                if (!Garnish.hasAttr(selectedItems.eq(i).find('.element'), 'data-deletable-for-site')) {
                    return false;
                }
            }

            return elementIndex.settings.canDeleteElements(selectedItems);
        },
    })
})();
JS, [static::class]);

        return null;
    }

    #[\Override]
    public function getTriggerLabel(): string
    {
        return t('Delete for site');
    }

    #[\Override]
    public static function isDestructive(): bool
    {
        return true;
    }

    public function getConfirmationMessage(): ?string
    {
        return $this->confirmationMessage ?? t('Are you sure you want to delete the selected {type} for this site?', [
            'type' => $this->elementType::pluralLowerDisplayName(),
        ]);
    }

    #[\Override]
    public function performAction(ElementQueryInterface $query): bool
    {
        // Ignore any elements the user doesn’t have permission to delete
        $elements = array_filter(
            $query->all(),
            fn (ElementInterface $element) => (
                Gate::check('view', $element) &&
                Gate::check('deleteForSite', $element)
            ),
        );

        Elements::deleteElementsForSite($elements);

        if (isset($this->successMessage)) {
            $this->setMessage($this->successMessage);
        } else {
            $this->setMessage(t('{type} deleted for site.', [
                'type' => $this->elementType::pluralDisplayName(),
            ]));
        }

        return true;
    }
}
