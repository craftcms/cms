<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Actions;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\Structures;
use Illuminate\Support\Facades\Gate;
use Throwable;

use function CraftCms\Cms\t;

class Duplicate extends ElementAction
{
    public bool $deep = false;

    public bool $asDrafts = false;

    public ?string $successMessage = null;

    #[\Override]
    public function getTriggerLabel(): string
    {
        return $this->deep
            ? t('Duplicate (with descendants)')
            : t('Duplicate');
    }

    public function getTriggerHtml(): ?string
    {
        // Only enable for duplicatable elements, per canDuplicate()
        HtmlStack::jsWithVars(fn ($type, $attr) => <<<JS
(() => {
  new Craft.ElementActionTrigger({
    type: $type,
    validateSelection: (selectedItems, elementIndex) => {
      for (let i = 0; i < selectedItems.length; i++) {
        if (!Garnish.hasAttr(selectedItems.eq(i).find('.element'), $attr)) {
          return false;
        }
      }

      return elementIndex.settings.canDuplicateElements(selectedItems);
    },
    beforeActivate: async (selectedItems, elementIndex) => {
      await elementIndex.onBeforeDuplicateElements(selectedItems);
    },
    afterActivate: async (selectedItems, elementIndex) => {
      await elementIndex.onDuplicateElements(selectedItems);
    },
  })
})();
JS, [
            static::class,
            $this->asDrafts ? 'data-duplicatable-as-draft' : 'data-duplicatable',
        ]);

        return null;
    }

    #[\Override]
    public function performAction(ElementQueryInterface $query): bool
    {
        if ($this->deep) {
            $query->orderBy('structureelements.lft');
        }

        $successCount = 0;
        $failCount = 0;

        $this->_duplicateElements($query, $successCount, $failCount);

        // Did all of them fail?
        if ($successCount === 0) {
            $this->setMessage(t('Could not duplicate elements due to validation errors.'));

            return false;
        }

        if ($failCount !== 0) {
            $this->setMessage(t('Could not duplicate all elements due to validation errors.'));
        } else {
            $this->setMessage(t('Elements duplicated.'));
        }

        return true;
    }

    /**
     * @param  array<int|string, true>  $duplicatedElementIds
     */
    private function _duplicateElements(ElementQueryInterface $query, int &$successCount, int &$failCount, array &$duplicatedElementIds = [], ?ElementInterface $newParent = null): void
    {
        foreach ($query->cursor() as $element) {
            $allowed = $this->asDrafts
                ? Gate::check('duplicateAsDraft', $element)
                : Gate::check('duplicate', $element);

            if (! $allowed) {
                continue;
            }

            // Make sure this element wasn't already duplicated, which could
            // happen if it's the descendant of a previously duplicated element
            // and $this->deep == true.
            if (isset($duplicatedElementIds[$element->id])) {
                continue;
            }

            $attributes = [
                'isProvisionalDraft' => false,
                'draftId' => null,
            ];

            // If the element was loaded for a non-primary owner, set its primary owner to it
            if ($element instanceof NestedElementInterface) {
                $attributes['primaryOwner'] = $element->getOwner();
                $attributes['sortOrder'] = null; // clear our sort order too
            }

            try {
                $duplicate = Elements::duplicateElement(
                    $element,
                    $attributes,
                    asUnpublishedDraft: $this->asDrafts,
                );
            } catch (Throwable) {
                // Validation error
                $failCount++;

                continue;
            }

            $successCount++;
            $duplicatedElementIds[$element->id] = true;

            if ($newParent) {
                // Append it to the duplicate of $element’s parent
                Structures::append($element->structureId, $duplicate, $newParent);
            } elseif ($element->structureId) {
                // Place it right next to the original element
                Structures::moveAfter($element->structureId, $duplicate, $element);
            }

            if ($this->deep) {
                // Don't use $element->children() here in case its lft/rgt values have changed
                $childQuery = $element::find()
                    ->siteId($element->siteId)
                    ->descendantOf($element->id)
                    ->descendantDist(1)
                    ->status(null)
                    ->all();

                $this->_duplicateElements($childQuery, $successCount, $failCount, $duplicatedElementIds, $duplicate);
            }
        }
    }
}
