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
                /** @var NestedElementInterface[] $sourceElements */
                $ownerId = $sourceElements[0]->getOwnerId();
                if (!$ownerId) {
                    return false;
                }

                return [
                    'elementType' => Craft::$app->getElements()->getElementTypeById($ownerId),
                    'map' => array_map(fn(NestedElementInterface $element) => [
                        'source' => $element->id,
                        'target' => match ($handle) {
                            'owner' => $element->getOwnerId(),
                            'primaryOwner' => $element->getPrimaryOwnerId(),
                        },
                    ], $sourceElements),
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
     * @var ElementInterface|false The primary owner element, or false if [[primaryOwnerId]] is invalid
     * @see getPrimaryOwner()
     * @see setPrimaryOwner()
     */
    private ElementInterface|false $_primaryOwner;

    /**
     * @var ElementInterface|false The owner element, or false if [[ownerId]] is invalid
     * @see getOwner()
     * @see setOwner()
     */
    private ElementInterface|false $_owner;

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

            $ownerType = Craft::$app->getElements()->getElementTypeById($primaryOwnerId);
            if (!$ownerType) {
                return null;
            }

            $this->_primaryOwner = $ownerType::find()
                ->id($primaryOwnerId)
                ->site('*')
                ->preferSites([$this->siteId])
                ->unique()
                ->status(null)
                ->drafts(null)
                ->provisionalDrafts(null)
                ->revisions(null)
                ->trashed(null)
                ->one() ?? false;

            if (!$this->_primaryOwner) {
                throw new InvalidConfigException("Invalid owner ID: $primaryOwnerId");
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

            $ownerType = Craft::$app->getElements()->getElementTypeById($ownerId);
            if (!$ownerType) {
                return null;
            }

            $this->_owner = $ownerType::find()
                ->id($ownerId)
                ->site('*')
                ->preferSites([$this->siteId])
                ->unique()
                ->status(null)
                ->drafts(null)
                ->provisionalDrafts(null)
                ->revisions(null)
                ->trashed(null)
                ->one() ?? false;

            if (!$this->_owner) {
                throw new InvalidConfigException("Invalid owner ID: $ownerId");
            }
        }

        return $this->_owner ?: null;
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

        $field = $this->getOwner()?->getFieldLayout()->getFieldById($this->fieldId)
            ?? Craft::$app->getFields()->getFieldById($this->fieldId);

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
                $this->setOwner(reset($elements));
                break;
            case 'primaryOwner':
                $this->setPrimaryOwner(reset($elements));
                break;
            default:
                parent::setEagerLoadedElements($handle, $elements, $plan);
        }
    }

    /**
     * Saves the element’s ownership data, if it belongs to a field + owner element
     */
    private function saveOwnership(bool $isNew, string $elementTable, string $fieldIdColumn = 'fieldId'): void
    {
        if (!$this->saveOwnership || !isset($this->fieldId)) {
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

        if (!$isNew) {
            Db::delete(Table::ELEMENTS_OWNERS, [
                'elementId' => $this->id,
                'ownerId' => $ownerId,
            ]);
        }

        Db::insert(Table::ELEMENTS_OWNERS, [
            'elementId' => $this->id,
            'ownerId' => $ownerId,
            'sortOrder' => $this->sortOrder,
        ]);
    }
}
