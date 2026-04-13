<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\Data\EagerLoadPlan;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tpetry\QueryExpressions\Language\Alias;

/**
 * NestedElement
 *
 * @property ElementInterface|null $primaryOwner the primary owner element
 * @property ElementInterface|null $owner the owner element
 * @property int|null $primaryOwnerId the primary owner element’s ID
 * @property int|null $ownerId the owner element’s ID
 * @property ElementContainerFieldInterface|null $field the element’s field
 *
 * @mixin Element
 */
trait NestedElement
{
    /**
     * @var int|null Primary owner ID
     */
    private ?int $primaryOwnerId = null;

    /**
     * @var int|null Owner ID
     */
    private ?int $ownerId = null;

    /**
     * @var class-string<ElementInterface>|null Owner type
     */
    private ?string $ownerType = null;

    /**
     * @var int|null Field ID
     */
    #[AllowedInSandbox]
    public ?int $fieldId = null;

    /**
     * @var int|null Sort order
     */
    public ?int $sortOrder = null;

    /**
     * @var bool Whether to save the element’s row in the `elements_owners` table from `afterSave()`.
     */
    public bool $saveOwnership = true;

    /**
     * @var bool Whether the search index should be updated for the owner element, alongside this element.
     *
     * This will only be checked if [[fieldId]] is set, and `false` isn’t passed to the `updateSearchIndex`
     * argument of [[\craft\services\Elements::saveElement()]].
     *
     * @since 5.2.0
     */
    public bool $updateSearchIndexForOwner = false;

    /**
     * @var ElementInterface|false|null The primary owner element, or false if [[primaryOwnerId]] is invalid
     *
     * @see getPrimaryOwner()
     * @see setPrimaryOwner()
     */
    private ElementInterface|false|null $_primaryOwner = null;

    /**
     * @var ElementInterface|false|null The owner element, or false if [[ownerId]] is invalid
     *
     * @see getOwner()
     * @see setOwner()
     */
    private ElementInterface|false|null $_owner = null;

    /**
     * @var ElementInterface[]
     *
     * @see getOwners()
     */
    private array $_owners;

    public static function eagerLoadingMap(array $sourceElements, string $handle): array|null|false
    {
        return match ($handle) {
            'owner', 'primaryOwner' => [
                /** @phpstan-ignore-next-line */
                'map' => array_filter(array_map(function (NestedElementInterface $element) use ($handle) {
                    $ownerId = match ($handle) {
                        'owner' => $element->getOwnerId(),
                        'primaryOwner' => $element->getPrimaryOwnerId(),
                    };

                    return $ownerId ? [
                        'source' => $element->id,
                        'target' => $ownerId,
                    ] : null;
                }, $sourceElements)),
                'criteria' => [
                    'status' => null,
                ],
            ],
            default => parent::eagerLoadingMap($sourceElements, $handle),
        };
    }

    public function __clone(): void
    {
        parent::__clone();

        $this->_primaryOwner = null;
        $this->_owner = null;
        $this->ownerType = null;
    }

    public function __get($name)
    {
        return match ($name) {
            'ownerId' => $this->getOwnerId(),
            'primaryOwnerId' => $this->getPrimaryOwnerId(),
            default => parent::__get($name),
        };
    }

    public function attributes(): array
    {
        $names = parent::attributes();
        $names[] = 'primaryOwnerId';
        $names[] = 'ownerId';

        return $names;
    }

    public function extraFields(): array
    {
        $names = parent::extraFields();
        $names[] = 'primaryOwner';
        $names[] = 'owner';

        return $names;
    }

    public function getPrimaryOwnerId(): ?int
    {
        return $this->primaryOwnerId ?? $this->ownerId;
    }

    public function setPrimaryOwnerId(?int $id): void
    {
        $this->primaryOwnerId = $id;

        if (! $id || $this->_primaryOwner === false || $this->_primaryOwner?->id !== $id) {
            $this->_primaryOwner = null;
        }
    }

    public function getPrimaryOwner(): ?ElementInterface
    {
        if (isset($this->_primaryOwner)) {
            return $this->_primaryOwner ?: null;
        }

        if (! $primaryOwnerId = $this->getPrimaryOwnerId()) {
            return null;
        }

        $sameSiteElements = isset($this->id, $this->elementQueryResult)
            ? array_filter($this->elementQueryResult, fn (ElementInterface $element) => $element->siteId === $this->siteId)
            : [];

        if (! empty($sameSiteElements)) {
            // Eager-load the primary owner for each of the elements in the result,
            // as we're probably going to end up needing them too
            Elements::eagerLoadElements($this::class, $sameSiteElements, [
                [
                    'path' => 'primaryOwner',
                    'criteria' => $this->ownerCriteria(),
                ],
            ]);
        }

        /** @phpstan-ignore-next-line */
        if (! isset($this->_primaryOwner) || $this->_primaryOwner === false) {
            // Either we didn't try, or the primary owner couldn't be eager-loaded for some reason
            if (! $ownerType = $this->ownerType()) {
                return null;
            }

            $query = $ownerType::find()->id($primaryOwnerId);
            Typecast::configure($query, $this->ownerCriteria());
            $this->_primaryOwner = $query->one() ?? false;

            if (! $this->_primaryOwner) {
                throw new RuntimeException("Invalid owner ID: $primaryOwnerId");
            }
        }

        return $this->_primaryOwner;
    }

    public function setPrimaryOwner(?ElementInterface $owner): void
    {
        $this->_primaryOwner = $owner ?? false;
        $this->primaryOwnerId = $owner->id ?? null;
    }

    public function getOwnerId(): ?int
    {
        return $this->ownerId ?? $this->primaryOwnerId;
    }

    public function setOwnerId(?int $id): void
    {
        $this->ownerId = $id;

        if (! $id || $this->_owner === false || $this->_owner?->id !== $id) {
            $this->_owner = null;
        }
    }

    public function getOwner(): ?ElementInterface
    {
        if (! isset($this->_owner)) {
            $ownerId = $this->getOwnerId();
            if (! $ownerId) {
                return null;
            }

            // If ownerId and primaryOwnerId are the same, return the primary owner
            if ($ownerId === $this->getPrimaryOwnerId()) {
                return $this->getPrimaryOwner();
            }

            $sameSiteElements = isset($this->id, $this->elementQueryResult)
                ? array_filter($this->elementQueryResult, fn (ElementInterface $element) => $element->siteId === $this->siteId)
                : [];

            if (! empty($sameSiteElements)) {
                // Eager-load the owner for each of the elements in the result,
                // as we're probably going to end up needing them too
                Elements::eagerLoadElements($this::class, $sameSiteElements, [
                    [
                        'path' => 'owner',
                        'criteria' => $this->ownerCriteria(),
                    ],
                ]);
            }

            /** @phpstan-ignore-next-line */
            if (! isset($this->_owner) || $this->_owner === false) {
                // Either we didn't try, or the owner couldn't be eager-loaded for some reason
                $ownerType = $this->ownerType();
                if (! $ownerType) {
                    return null;
                }

                $query = $ownerType::find()->id($ownerId);
                Typecast::configure($query, $this->ownerCriteria());
                $this->_owner = $query->one() ?? false;

                if (! $this->_owner) {
                    throw new RuntimeException("Invalid owner ID: $ownerId");
                }
            }
        }

        return $this->_owner ?: null;
    }

    public function getOwners(array $criteria = []): array
    {
        if (! isset($this->_owners)) {
            $this->_owners = [];
            $ownerType = $this->ownerType();
            if ($ownerType) {
                $ownerIds = DB::table(Table::ELEMENTS_OWNERS)
                    ->where('elementId', $this->id)
                    ->pluck('ownerId')
                    ->all();

                if (! empty($ownerIds)) {
                    $query = $ownerType::find()->id($ownerIds);
                    Typecast::configure($query, $criteria + $this->ownerCriteria());
                    $this->_owners = $query->all();
                }
            }
        }

        return $this->_owners;
    }

    private function ownerCriteria(): array
    {
        return [
            'site' => '*',
            'preferSites' => [$this->siteId],
            'unique' => true,
            'status' => null,
            'drafts' => null,
            'provisionalDrafts' => null,
            'revisions' => null,
            'trashed' => null,
        ];
    }

    public function setOwner(?ElementInterface $owner): void
    {
        $this->_owner = $owner ?? false;
        $this->ownerId = $owner->id ?? null;
    }

    public function getField(): ?ElementContainerFieldInterface
    {
        if (! isset($this->fieldId)) {
            return null;
        }

        $field = null;

        try {
            $field = $this->getOwner()?->getFieldLayout()?->getFieldById($this->fieldId);
        } catch (RuntimeException) {
            // carry on as we might still be able to get the field by ID
        }

        if (! $field) {
            $field = app(Fields::class)->getFieldById($this->fieldId);
        }

        if (! $field instanceof ElementContainerFieldInterface) {
            throw new RuntimeException("Invalid field ID: $this->fieldId");
        }

        return $field;
    }

    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(?int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    public function setSaveOwnership(bool $saveOwnership): void
    {
        $this->saveOwnership = $saveOwnership;
    }

    public function addInvalidNestedElementIds(array $ids): void
    {
        parent::addInvalidNestedElementIds($ids);

        if (isset($this->_owner)) {
            $this->_owner->addInvalidNestedElementIds($ids);
        }
    }

    public function setEagerLoadedElements(string $handle, array $elements, EagerLoadPlan $plan): void
    {
        match ($plan->handle) {
            'owner' => $this->setOwner(reset($elements) ?: null),
            'primaryOwner' => $this->setPrimaryOwner(reset($elements) ?: null),
            default => parent::setEagerLoadedElements($handle, $elements, $plan),
        };
    }

    /**
     * Returns the owner element’s type.
     *
     * @return class-string<ElementInterface>|null
     */
    protected function ownerType(): ?string
    {
        if (isset($this->ownerType)) {
            return $this->ownerType;
        }

        if (! $ownerId = $this->getOwnerId()) {
            return null;
        }

        if (! $ownerType = Elements::getElementTypeById($ownerId)) {
            return null;
        }

        return $this->ownerType = $ownerType;
    }

    /**
     * Saves the element’s ownership data, if it belongs to a field + owner element
     */
    private function saveOwnership(bool $isNew, string $elementTable, string $fieldIdColumn = 'fieldId'): void
    {
        if (! $this->saveOwnership || ! isset($this->fieldId) || $this->resaving) {
            return;
        }

        if (! $ownerId = $this->getOwnerId()) {
            return;
        }

        if (! isset($this->sortOrder) && (! $isNew || $this->duplicateOf)) {
            // figure out if we should proceed this way
            // if we're dealing with an element that's being duplicated, and it has a draftId
            // it means we're creating a draft of something
            // if we're duplicating element via duplicate action - draftId would be empty
            $elementId = null;

            if ($this->duplicateOf) {
                if ($this->draftId) {
                    $elementId = $this->duplicateOf->id;
                }
            } else {
                // if we're not duplicating, use this element's id
                $elementId = $this->id;
            }

            if ($elementId) {
                $this->sortOrder = DB::table(Table::ELEMENTS_OWNERS)
                    ->where('elementId', $elementId)
                    ->where('ownerId', $ownerId)
                    ->value('sortOrder') ?: null;
            }
        }

        if (! isset($this->sortOrder)) {
            $max = DB::table(Table::ELEMENTS_OWNERS, 'eo')
                ->join(new Alias($elementTable, 'e'), 'e.id', '=', 'eo.elementId')
                ->where('eo.ownerId', $ownerId)
                ->where("e.$fieldIdColumn", $this->fieldId)
                ->max('eo.sortOrder');

            $this->sortOrder = $max ? $max + 1 : 1;
        }

        $ownerIds = array_unique([
            $this->getPrimaryOwnerId(),
            $ownerId,
        ]);

        if (! $isNew) {
            DB::table(Table::ELEMENTS_OWNERS)
                ->where('elementId', $this->id)
                ->whereIn('ownerId', $ownerIds)
                ->delete();
        }

        foreach ($ownerIds as $ownerId) {
            DB::table(Table::ELEMENTS_OWNERS)->insert([
                'elementId' => $this->id,
                'ownerId' => $ownerId,
                'sortOrder' => $this->sortOrder,
            ]);
        }
    }
}
