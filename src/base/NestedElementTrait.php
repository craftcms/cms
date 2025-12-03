<?php

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\elements\db\EagerLoadPlan;
use craft\helpers\Db;
use yii\base\InvalidConfigException;

/**
 * NestedElementTrait
 *
 * @property ElementInterface|null $primaryOwner the primary owner element
 * @property ElementInterface|null $owner the owner element
 * @property int|null $primaryOwnerId the primary owner element’s ID
 * @property int|null $ownerId the owner element’s ID
 * @property ElementContainerFieldInterface|null $field the element’s field
 * @mixin Element
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
trait NestedElementTrait
{
    /**
     * @inheritdoc
     */
    public static function eagerLoadingMap(array $sourceElements, string $handle): array|null|false
    {
        switch ($handle) {
            case 'owner':
            case 'primaryOwner':
                /** @phpstan-ignore-next-line */
                return [
                    /** @phpstan-ignore-next-line */
                    'map' => array_filter(array_map(function(NestedElementInterface $element) use ($handle) {
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
                ];
            default:
                return parent::eagerLoadingMap($sourceElements, $handle);
        }
    }

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
     * @see getPrimaryOwner()
     * @see setPrimaryOwner()
     */
    private ElementInterface|false|null $_primaryOwner = null;

    /**
     * @var ElementInterface|false|null The owner element, or false if [[ownerId]] is invalid
     * @see getOwner()
     * @see setOwner()
     */
    private ElementInterface|false|null $_owner = null;

    /**
     * @var ElementInterface[]
     * @see getOwners()
     */
    private array $_owners;

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

    /**
     * @inheritdoc
     */
    public function attributes(): array
    {
        $names = parent::attributes();
        $names[] = 'primaryOwnerId';
        $names[] = 'ownerId';
        return $names;
    }

    /**
     * @inheritdoc
     */
    public function extraFields(): array
    {
        $names = parent::extraFields();
        $names[] = 'primaryOwner';
        $names[] = 'owner';
        return $names;
    }

    /**
     * @inheritdoc
     */
    public function getPrimaryOwnerId(): ?int
    {
        return $this->primaryOwnerId ?? $this->ownerId;
    }

    /**
     * @inheritdoc
     */
    public function setPrimaryOwnerId(?int $id): void
    {
        $this->primaryOwnerId = $id;

        if (!$id || $this->_primaryOwner === false || $this->_primaryOwner?->id !== $id) {
            $this->_primaryOwner = null;
        }
    }

    /**
     * @inheritdoc
     */
    public function getPrimaryOwner(): ?ElementInterface
    {
        if (!isset($this->_primaryOwner)) {
            $primaryOwnerId = $this->getPrimaryOwnerId();
            if (!$primaryOwnerId) {
                return null;
            }

            $sameSiteElements = isset($this->id, $this->elementQueryResult)
                ? array_filter($this->elementQueryResult, fn(ElementInterface $element) => $element->siteId === $this->siteId)
                : [];

            if (!empty($sameSiteElements)) {
                // Eager-load the primary owner for each of the elements in the result,
                // as we're probably going to end up needing them too
                Craft::$app->getElements()->eagerLoadElements($this::class, $sameSiteElements, [
                    [
                        'path' => 'primaryOwner',
                        'criteria' => $this->ownerCriteria(),
                    ],
                ]);
            }

            /** @phpstan-ignore-next-line */
            if (!isset($this->_primaryOwner) || $this->_primaryOwner === false) {
                // Either we didn't try, or the primary owner couldn't be eager-loaded for some reason
                $ownerType = $this->ownerType();
                if (!$ownerType) {
                    return null;
                }

                $query = $ownerType::find()->id($primaryOwnerId);
                Craft::configure($query, $this->ownerCriteria());
                $this->_primaryOwner = $query->one() ?? false;

                if (!$this->_primaryOwner) {
                    throw new InvalidConfigException("Invalid owner ID: $primaryOwnerId");
                }
            }
        }

        return $this->_primaryOwner ?: null;
    }

    /**
     * @inheritdoc
     */
    public function setPrimaryOwner(?ElementInterface $owner): void
    {
        $this->_primaryOwner = $owner ?? false;
        $this->primaryOwnerId = $owner->id ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getOwnerId(): ?int
    {
        return $this->ownerId ?? $this->primaryOwnerId;
    }

    /**
     * @inheritdoc
     */
    public function setOwnerId(?int $id): void
    {
        $this->ownerId = $id;

        if (!$id || $this->_owner === false || $this->_owner?->id !== $id) {
            $this->_owner = null;
        }
    }

    /**
     * @inheritdoc
     */
    public function getOwner(): ?ElementInterface
    {
        if (!isset($this->_owner)) {
            $ownerId = $this->getOwnerId();
            if (!$ownerId) {
                return null;
            }

            // If ownerId and primaryOwnerId are the same, return the primary owner
            if ($ownerId === $this->getPrimaryOwnerId()) {
                return $this->getPrimaryOwner();
            }

            $sameSiteElements = isset($this->id, $this->elementQueryResult)
                ? array_filter($this->elementQueryResult, fn(ElementInterface $element) => $element->siteId === $this->siteId)
                : [];

            if (!empty($sameSiteElements)) {
                // Eager-load the owner for each of the elements in the result,
                // as we're probably going to end up needing them too
                Craft::$app->getElements()->eagerLoadElements($this::class, $sameSiteElements, [
                    [
                        'path' => 'owner',
                        'criteria' => $this->ownerCriteria(),
                    ],
                ]);
            }

            /** @phpstan-ignore-next-line */
            if (!isset($this->_owner) || $this->_owner === false) {
                // Either we didn't try, or the owner couldn't be eager-loaded for some reason
                $ownerType = $this->ownerType();
                if (!$ownerType) {
                    return null;
                }

                $query = $ownerType::find()->id($ownerId);
                Craft::configure($query, $this->ownerCriteria());
                $this->_owner = $query->one() ?? false;

                if (!$this->_owner) {
                    throw new InvalidConfigException("Invalid owner ID: $ownerId");
                }
            }
        }

        return $this->_owner ?: null;
    }

    /**
     * @inheritdoc
     */
    public function getOwners(array $criteria = []): array
    {
        if (!isset($this->_owners)) {
            $this->_owners = [];
            $ownerType = $this->ownerType();
            if ($ownerType) {
                $ownerIds = (new Query())
                    ->select('ownerId')
                    ->from(Table::ELEMENTS_OWNERS)
                    ->where(['elementId' => $this->id])
                    ->column();
                if (!empty($ownerIds)) {
                    $query = $ownerType::find()
                        ->id($ownerIds);
                    Craft::configure($query, $criteria + $this->ownerCriteria());
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

    /**
     * @inheritdoc
     */
    public function setOwner(?ElementInterface $owner): void
    {
        $this->_owner = $owner ?? false;
        $this->ownerId = $owner->id ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getField(): ?ElementContainerFieldInterface
    {
        if (!isset($this->fieldId)) {
            return null;
        }

        $field = null;

        try {
            $field = $this->getOwner()?->getFieldLayout()->getFieldById($this->fieldId);
        } catch (InvalidConfigException $e) {
            // carry on as we might still be able to get the field by ID
        }

        if (!$field) {
            $field = Craft::$app->getFields()->getFieldById($this->fieldId);
        }

        if (!$field instanceof ElementContainerFieldInterface) {
            throw new InvalidConfigException("Invalid field ID: $this->fieldId");
        }

        return $field;
    }

    /**
     * @inheritdoc
     */
    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    /**
     * @inheritdoc
     */
    public function setSortOrder(?int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    /**
     * @inheritdoc
     */
    public function setSaveOwnership(bool $saveOwnership): void
    {
        $this->saveOwnership = $saveOwnership;
    }

    /**
     * @inheritdoc
     */
    public function addInvalidNestedElementIds(array $ids): void
    {
        parent::addInvalidNestedElementIds($ids);

        if (isset($this->_owner)) {
            $this->_owner->addInvalidNestedElementIds($ids);
        }
    }

    /**
     * @inheritdoc
     */
    public function setEagerLoadedElements(string $handle, array $elements, EagerLoadPlan $plan): void
    {
        switch ($plan->handle) {
            case 'owner':
                $this->setOwner(reset($elements) ?: null);
                break;
            case 'primaryOwner':
                $this->setPrimaryOwner(reset($elements) ?: null);
                break;
            default:
                parent::setEagerLoadedElements($handle, $elements, $plan);
        }
    }

    /**
     * Returns the owner element’s type.
     *
     * @return class-string<ElementInterface>|null
     * @since 5.6.0
     */
    protected function ownerType(): ?string
    {
        if (!isset($this->ownerType)) {
            $ownerId = $this->getOwnerId();
            if (!$ownerId) {
                return null;
            }
            $ownerType = Craft::$app->getElements()->getElementTypeById($ownerId);
            if (!$ownerType) {
                return null;
            }
            $this->ownerType = $ownerType;
        }
        return $this->ownerType;
    }

    /**
     * Saves the element’s ownership data, if it belongs to a field + owner element
     */
    private function saveOwnership(bool $isNew, string $elementTable, string $fieldIdColumn = 'fieldId'): void
    {
        if (!$this->saveOwnership || !isset($this->fieldId) || $this->resaving) {
            return;
        }
        
        $ownerId = $this->getOwnerId();
        if (!$ownerId) {
            return;
        }

        if (!isset($this->sortOrder) && (!$isNew || $this->duplicateOf)) {
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
                $this->sortOrder = (new Query())
                    ->select('sortOrder')
                    ->from(Table::ELEMENTS_OWNERS)
                    ->where([
                        'elementId' => $elementId,
                        'ownerId' => $ownerId,
                    ])
                    ->scalar() ?: null;
            }
        }

        if (!isset($this->sortOrder)) {
            $max = (new Query())
                ->from(['eo' => Table::ELEMENTS_OWNERS])
                ->innerJoin(['e' => $elementTable], '[[e.id]] = [[eo.elementId]]')
                ->where([
                    'eo.ownerId' => $ownerId,
                    "e.$fieldIdColumn" => $this->fieldId,
                ])
                ->max('[[eo.sortOrder]]');
            $this->sortOrder = $max ? $max + 1 : 1;
        }

        $ownerIds = array_unique([
            $this->getPrimaryOwnerId(),
            $ownerId,
        ]);

        if (!$isNew) {
            Db::delete(Table::ELEMENTS_OWNERS, [
                'elementId' => $this->id,
                'ownerId' => $ownerIds,
            ]);
        }

        foreach ($ownerIds as $ownerId) {
            Db::insert(Table::ELEMENTS_OWNERS, [
                'elementId' => $this->id,
                'ownerId' => $ownerId,
                'sortOrder' => $this->sortOrder,
            ]);
        }
    }
}
