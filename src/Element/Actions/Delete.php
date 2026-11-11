<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Actions;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\DeleteActionInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Support\Facades\Drafts;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Workflows;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Override;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
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
        $failedElementIds = [];

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
                            if ($this->deleteElement($descendant, $deleteOwnership)) {
                                $deletedElementIds[$descendant->id] = true;
                                unset($failedElementIds[$descendant->id]);
                            } else {
                                $failedElementIds[$descendant->id] = true;
                            }
                        }
                    }
                }
                if ($this->deleteElement($element, $deleteOwnership)) {
                    $deletedElementIds[$element->id] = true;
                    unset($failedElementIds[$element->id]);
                } else {
                    $failedElementIds[$element->id] = true;
                }
            }
        }

        foreach ($deleteOwnership as $ownerId => $data) {
            $owner = $data['owner'];
            Workflows::withContentChangeLock(Drafts::getDraftIdsForElement($owner), function () use ($ownerId, $data, $owner): void {
                DB::table(Table::ELEMENTS_OWNERS)
                    ->whereIn('elementId', $data['elementIds'])
                    ->where('ownerId', $ownerId)
                    ->delete();

                Elements::touchElementAndOwners($owner);
            });
        }

        if ($failedElementIds !== []) {
            $this->setMessage(t('Could not delete {count, number} {count, plural, =1{{typeSingular}} other{{typePlural}}}.', [
                'count' => count($failedElementIds),
                'typeSingular' => $this->elementType::lowerDisplayName(),
                'typePlural' => $this->elementType::pluralLowerDisplayName(),
            ]));

            return false;
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
     * @param  array<int, array{owner: ElementInterface, elementIds: list<int|null>}>  $deleteOwnership
     */
    private function deleteElement(
        ElementInterface $element,
        array &$deleteOwnership,
    ): bool {
        // If the element primarily belongs to a different element, (and we're not hard deleting) just delete the ownership
        if (! $this->hard && $element instanceof NestedElementInterface) {
            $ownerId = $element->getOwnerId();
            if ($ownerId && $element->getPrimaryOwnerId() !== $ownerId) {
                $owner = $deleteOwnership[$ownerId]['owner'] ?? $element->getOwner();
                if ($owner !== null) {
                    $deleteOwnership[$ownerId] ??= ['owner' => $owner, 'elementIds' => []];
                    $deleteOwnership[$ownerId]['elementIds'][] = $element->id;

                    return true;
                }
            }
        }

        return Elements::deleteElement($element, $this->hard);
    }
}
