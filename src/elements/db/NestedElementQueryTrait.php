<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\db;

use Craft;
use craft\base\ElementContainerFieldInterface;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\db\QueryAbortedException;
use craft\db\Table;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;

/**
 * Trait NestedElementQueryTrait
 *
 * @mixin ElementQuery
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.5.0
 */
trait NestedElementQueryTrait
{
    /**
     * @var mixed The field ID(s) that the resulting {elements} must belong to.
     * @used-by fieldId()
     * @since 5.0.0
     */
    public mixed $fieldId = null;

    /**
     * @var mixed The primary owner element ID(s) that the resulting {elements} must belong to.
     * @used-by primaryOwner()
     * @used-by primaryOwnerId()
     * @since 5.0.0
     */
    public mixed $primaryOwnerId = null;

    /**
     * @var mixed The owner element ID(s) that the resulting {elements} must belong to.
     * @used-by owner()
     * @used-by ownerId()
     * @since 5.0.0
     */
    public mixed $ownerId = null;

    /**
     * @var ElementInterface|null The owner element specified by [[owner()]].
     * @used-by owner()
     */
    private ?ElementInterface $_owner = null;

    /**
     * @var bool|null Whether the owner elements can be drafts.
     * @used-by allowOwnerDrafts()
     * @since 5.0.0
     */
    public ?bool $allowOwnerDrafts = null;

    /**
     * @var bool|null Whether the owner elements can be revisions.
     * @used-by allowOwnerRevisions()
     * @since 5.0.0
     */
    public ?bool $allowOwnerRevisions = null;

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        switch ($name) {
            case 'field':
                $this->field($value);
                break;
            case 'owner':
                $this->owner($value);
                break;
            case 'primaryOwner':
                $this->primaryOwner($value);
                break;
            default:
                parent::__set($name, $value);
        }
    }

    /**
     * @inheritdoc
     * @uses $fieldId
     * @since 5.0.0
     */
    public function field(mixed $value): static
    {
        if (Db::normalizeParam($value, function($item) {
            if (is_string($item)) {
                $item = Craft::$app->getFields()->getFieldByHandle($item);
            }
            return $item instanceof ElementContainerFieldInterface ? $item->id : null;
        })) {
            $this->fieldId = $value;
        } else {
            $this->fieldId = false;
        }

        return $this;
    }

    /**
     * @inheritdoc
     * @uses $fieldId
     * @since 5.0.0
     */
    public function fieldId(mixed $value): static
    {
        $this->fieldId = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $primaryOwnerId
     * @since 5.0.0
     */
    public function primaryOwnerId(mixed $value): static
    {
        $this->primaryOwnerId = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $primaryOwnerId
     * @since 5.0.0
     */
    public function primaryOwner(ElementInterface $primaryOwner): static
    {
        $this->primaryOwnerId = [$primaryOwner->id];
        $this->siteId = $primaryOwner->siteId;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $ownerId
     * @since 5.0.0
     */
    public function ownerId(mixed $value): static
    {
        $this->ownerId = $value;
        $this->_owner = null;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $ownerId
     * @since 5.0.0
     */
    public function owner(ElementInterface $owner): static
    {
        $this->ownerId = [$owner->id];
        $this->siteId = $owner->siteId;
        $this->_owner = $owner;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $allowOwnerDrafts
     * @since 5.0.0
     */
    public function allowOwnerDrafts(?bool $value = true): static
    {
        $this->allowOwnerDrafts = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $allowOwnerRevisions
     * @since 5.0.0
     */
    public function allowOwnerRevisions(?bool $value = true): static
    {
        $this->allowOwnerRevisions = $value;
        return $this;
    }

    private function applyNestedElementParams(string $fieldIdColumn, string $primaryOwnerIdColumn): void
    {
        $this->normalizeNestedElementParams();

        if ($this->fieldId === false || $this->primaryOwnerId === false || $this->ownerId === false) {
            throw new QueryAbortedException();
        }

        if (!empty($this->fieldId) || !empty($this->ownerId) || !empty($this->primaryOwnerId)) {
            // Join in the elements_owners table
            $ownersCondition = [
                'and',
                '[[elements_owners.elementId]] = [[elements.id]]',
                $this->ownerId ? ['elements_owners.ownerId' => $this->ownerId] : "[[elements_owners.ownerId]] = [[$primaryOwnerIdColumn]]",
            ];

            $this->query
                ->addSelect([
                    'elements_owners.ownerId',
                    'elements_owners.sortOrder',
                ])
                ->innerJoin(['elements_owners' => Table::ELEMENTS_OWNERS], $ownersCondition);
            $this->subQuery->innerJoin(['elements_owners' => Table::ELEMENTS_OWNERS], $ownersCondition);

            if ($this->fieldId) {
                $this->subQuery->andWhere([$fieldIdColumn => $this->fieldId]);
            }

            if ($this->primaryOwnerId) {
                $this->subQuery->andWhere([$primaryOwnerIdColumn => $this->primaryOwnerId]);
            }

            // Ignore revision/draft blocks by default
            $allowOwnerDrafts = $this->allowOwnerDrafts ?? ($this->id || $this->primaryOwnerId || $this->ownerId);
            $allowOwnerRevisions = $this->allowOwnerRevisions ?? ($this->id || $this->primaryOwnerId || $this->ownerId);

            if (!$allowOwnerDrafts || !$allowOwnerRevisions) {
                $this->subQuery->innerJoin(
                    ['owners' => Table::ELEMENTS],
                    $this->ownerId ? '[[owners.id]] = [[elements_owners.ownerId]]' : "[[owners.id]] = [[$primaryOwnerIdColumn]]"
                );

                if (!$allowOwnerDrafts) {
                    $this->subQuery->andWhere(['owners.draftId' => null]);
                }

                if (!$allowOwnerRevisions) {
                    $this->subQuery->andWhere(['owners.revisionId' => null]);
                }
            }

            $this->defaultOrderBy = ['elements_owners.sortOrder' => SORT_ASC];
        }
    }

    /**
     * Normalizes the `fieldId`, `primaryOwnerId`, and `ownerId` params.
     */
    private function normalizeNestedElementParams(): void
    {
        $this->normalizeFieldId();
        $this->primaryOwnerId = $this->normalizeOwnerId($this->primaryOwnerId);
        $this->ownerId = $this->normalizeOwnerId($this->ownerId);
    }

    /**
     * Normalizes the fieldId param to an array of IDs or null
     */
    private function normalizeFieldId(): void
    {
        if ($this->fieldId === false) {
            return;
        }

        if (empty($this->fieldId)) {
            $this->fieldId = is_array($this->fieldId) ? [] : null;
        } elseif (is_numeric($this->fieldId)) {
            $this->fieldId = [$this->fieldId];
        } elseif (!is_array($this->fieldId) || !ArrayHelper::isNumeric($this->fieldId)) {
            $this->fieldId = (new Query())
                ->select(['id'])
                ->from([Table::FIELDS])
                ->where(Db::parseNumericParam('id', $this->fieldId))
                ->column();
        }
    }

    /**
     * Normalizes the primaryOwnerId param to an array of IDs or null
     *
     * @param mixed $value
     * @return int[]|null|false
     */
    private function normalizeOwnerId(mixed $value): array|null|false
    {
        if (empty($value)) {
            return null;
        }
        if (is_numeric($value)) {
            return [$value];
        }
        if (!is_array($value) || !ArrayHelper::isNumeric($value)) {
            return false;
        }
        return $value;
    }

    /**
     * @inheritdoc
     */
    public function createElement(array $row): ElementInterface
    {
        if (isset($this->_owner)) {
            $row['owner'] = $this->_owner;
        }

        return parent::createElement($row);
    }

    /**
     * @inheritdoc
     */
    protected function cacheTags(): array
    {
        $tags = [];

        if ($this->fieldId) {
            foreach ($this->fieldId as $fieldId) {
                $tags[] = "field:$fieldId";
            }
        }

        if ($this->primaryOwnerId) {
            foreach ($this->primaryOwnerId as $ownerId) {
                $tags[] = "element::$ownerId";
            }
        }

        if ($this->ownerId) {
            foreach ($this->ownerId as $ownerId) {
                $tags[] = "element::$ownerId";
            }
        }

        return $tags;
    }

    /**
     * @inheritdoc
     */
    protected function fieldLayouts(): array
    {
        $this->normalizeFieldId();

        if ($this->fieldId) {
            $fieldLayouts = [];
            foreach ($this->fieldId as $fieldId) {
                $field = Craft::$app->getFields()->getFieldById($fieldId);
                if ($field instanceof ElementContainerFieldInterface) {
                    foreach ($field->getFieldLayoutProviders() as $provider) {
                        $fieldLayouts[] = $provider->getFieldLayout();
                    }
                }
            }
            return $fieldLayouts;
        }

        return parent::fieldLayouts();
    }
}
