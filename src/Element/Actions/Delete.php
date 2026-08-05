<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Actions;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\DeleteActionInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\HtmlStack;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Override;

use function CraftCms\Cms\t;

class Delete extends ElementAction implements DeleteActionInterface
{
    public bool $withDescendants = false;

    public bool $hard = false;

    public ?string $confirmationMessage = null;

    public ?string $successMessage = null;

    private readonly string $triggerId;

    public function __construct(object|array $config = [])
    {
        parent::__construct($config);

        $this->triggerId = sprintf('action-trigger-%s', mt_rand());
    }

    #[Override]
    public function getTriggerId(): string
    {
        return $this->triggerId;
    }

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
        HtmlStack::jsWithVars(fn (
            $triggerId,
            $elementType,
            $withDescendants,
            $hardDelete,
            $confirmationMessage,
        ) => <<<JS
(() => {
  new Craft.ElementActionTrigger({
    triggerId: $triggerId,
    validateSelection: (selectedItems, elementIndex) => {
      for (let i = 0; i < selectedItems.length; i++) {
        if (!Garnish.hasAttr(selectedItems.eq(i).find('.element'), 'data-deletable')) {
          return false;
        }
      }

      return elementIndex.settings.canDeleteElements(selectedItems);
    },
    activate: async (selectedItems, elementIndex) => {
      await elementIndex.onBeforeDeleteElements(selectedItems);
      elementIndex.setIndexBusy();
      const elementIds = elementIndex.getSelectedElementIds();

      new Craft.ElementDeletionManager($elementType, elementIds, {
        siteId: elementIndex.siteId,
        ownerId: elementIndex.settings.criteria?.ownerId,
        withDescendants: $withDescendants,
        hardDelete: $hardDelete,
        confirmationMessage: $confirmationMessage,
        onLoadBlockers: () => {
          elementIndex.setIndexAvailable();
        },
        onSuccess: async () => {
          elementIndex.updateElements(true, true);
          await elementIndex.onDeleteElements(selectedItems);
        },
      })
    },
  })
})();
JS, [
            $this->getTriggerId(),
            $this->elementType,
            $this->withDescendants,
            $this->hard,
            $this->confirmationMessage,
        ]);

        return null;
    }

    #[Override]
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

    #[Override]
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

    #[Override]
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

        foreach ($query->cursor() as $element) {
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

    /**
     * @param  array<int, array<int|null>>  $deleteOwnership
     */
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

        Elements::deleteElement($element, $this->hard);
    }
}
