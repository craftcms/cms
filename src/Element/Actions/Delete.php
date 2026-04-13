<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Actions;

use craft\base\NestedElementInterface;
use craft\services\Elements;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\DeleteActionInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Html;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

use function CraftCms\Cms\t;

class Delete extends ElementAction implements DeleteActionInterface
{
    public bool $withDescendants = false;

    public bool $hard = false;

    public ?string $confirmationMessage = null;

    public ?string $successMessage = null;

    public function canHardDelete(): bool
    {
        return ! $this->withDescendants;
    }

    public function setHardDelete(): void
    {
        $this->hard = true;
    }

    public function getTriggerHtml(): ?string
    {
        // Only enable for deletable elements, per canDelete()
        HtmlStack::jsWithVars(fn ($type) => <<<JS
(() => {
  new Craft.ElementActionTrigger({
    type: $type,
    validateSelection: (selectedItems, elementIndex) => {
      for (let i = 0; i < selectedItems.length; i++) {
        if (!Garnish.hasAttr(selectedItems.eq(i).find('.element'), 'data-deletable')) {
          return false;
        }
      }

      return elementIndex.settings.canDeleteElements(selectedItems);
    },
    beforeActivate: async (selectedItems, elementIndex) => {
      await elementIndex.settings.onBeforeDeleteElements(selectedItems);
    },
    afterActivate: async (selectedItems, elementIndex) => {
      await elementIndex.settings.onDeleteElements(selectedItems);
    },
  })
})();
JS, [static::class]);

        if ($this->hard) {
            return Html::tag('div', $this->getTriggerLabel(), [
                'class' => ['btn', 'formsubmit'],
            ]);
        }

        return null;
    }

    #[\Override]
    public function getTriggerLabel(): string
    {
        if ($this->hard) {
            return t('Delete permanently');
        }

        if ($this->withDescendants) {
            return t('Delete (with descendants)');
        }

        return t('Delete');
    }

    #[\Override]
    public static function isDestructive(): bool
    {
        return true;
    }

    public function getConfirmationMessage(): ?string
    {
        if (isset($this->confirmationMessage)) {
            return $this->confirmationMessage;
        }

        if ($this->hard) {
            return t('Are you sure you want to permanently delete the selected {type}?', [
                'type' => $this->elementType::pluralLowerDisplayName(),
            ]);
        }

        if ($this->withDescendants) {
            return t('Are you sure you want to delete the selected {type} along with their descendants?', [
                'type' => $this->elementType::pluralLowerDisplayName(),
            ]);
        }

        return t('Are you sure you want to delete the selected {type}?', [
            'type' => $this->elementType::pluralLowerDisplayName(),
        ]);
    }

    #[\Override]
    public function performAction(ElementQueryInterface $query): bool
    {
        $withDescendants = $this->withDescendants && ! $this->hard;

        if ($withDescendants) {
            $query
                ->with([
                    [
                        'descendants',
                        [
                            'orderByDesc' => 'structureelements.lft',
                            'status' => null,
                        ],
                    ],
                ])
                ->orderByDesc('structureelements.lft');
        }

        $deletedElementIds = [];
        $deleteOwnership = [];

        foreach ($query->all() as $element) {
            if (! Gate::check('view', $element)) {
                continue;
            }
            if (! Gate::check('delete', $element)) {
                continue;
            }
            if (! isset($deletedElementIds[$element->id])) {
                if ($withDescendants) {
                    foreach ($element->getDescendants()->all() as $descendant) {
                        if (
                            ! isset($deletedElementIds[$descendant->id]) &&
                            Gate::check('view', $descendant) &&
                            Gate::check('delete', $descendant)
                        ) {
                            $this->deleteElement($descendant, $deleteOwnership);
                            $deletedElementIds[$descendant->id] = true;
                        }
                    }
                }
                $this->deleteElement($element, $deleteOwnership);
                $deletedElementIds[$element->id] = true;
            }
        }

        foreach ($deleteOwnership as $ownerId => $elementIds) {
            DB::table(Table::ELEMENTS_OWNERS)
                ->whereIn('elementId', $elementIds)
                ->where('ownerId', $ownerId)
                ->delete();
        }

        if (isset($this->successMessage)) {
            $this->setMessage($this->successMessage);
        } else {
            $this->setMessage(t('{type} deleted.', [
                'type' => $this->elementType::pluralDisplayName(),
            ]));
        }

        return true;
    }

    private function deleteElement(
        ElementInterface $element,
        array &$deleteOwnership,
    ): void {
        // If the element primarily belongs to a different element, (and we're not hard deleting) just delete the ownership
        if (! $this->hard && $element instanceof NestedElementInterface) {
            $ownerId = $element->getOwnerId();
            if ($ownerId && $element->getPrimaryOwnerId() !== $ownerId) {
                $deleteOwnership[$ownerId][] = $element->id;

                return;
            }
        }

        \CraftCms\Cms\Support\Facades\Elements::deleteElement($element, $this->hard);
    }
}
