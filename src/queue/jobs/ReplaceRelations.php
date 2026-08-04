<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue\jobs;

use Craft;
use craft\base\Batchable;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\behaviors\CustomFieldBehavior;
use craft\db\QueryBatcher;
use craft\elements\db\ElementQueryInterface;
use craft\fields\BaseRelationField;
use craft\fields\Link;
use craft\fields\linktypes\BaseElementLinkType;
use craft\fields\linktypes\BaseLinkType;
use craft\i18n\Translation;
use craft\queue\BaseBatchedElementJob;
use craft\services\Elements;
use Illuminate\Support\Collection;
use Throwable;

/**
 * ReplaceRelations job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.10.0
 */
class ReplaceRelations extends BaseBatchedElementJob
{
    /**
     * @var class-string<ElementInterface> The element type that contains the relations
     */
    public string $sourceElementType;

    /**
     * @var class-string<ElementInterface> The element type that is being related
     */
    public string $targetElementType;

    /**
     * @var int[] The source element IDs to update
     */
    public array $sourceIds;

    /**
     * @var int[] The element IDs to replace
     */
    public array $oldTargetIds;

    /**
     * @var int The element ID to use as a replacement
     */
    public int $newTargetId;

    /**
     * @inheritdoc
     */
    protected function loadData(): Batchable
    {
        $query = $this->sourceElementType::find()
            ->id($this->sourceIds)
            ->siteId('*')
            ->orderBy([
                'elements.id' => SORT_ASC,
                'elements_sites.siteId' => SORT_ASC,
            ]);

        return new QueryBatcher($query);
    }

    /**
     * @inheritdoc
     */
    protected function processItem(mixed $item): void
    {
        /** @var ElementInterface $item */
        $customFields = Collection::make($item->getFieldLayout()?->getCustomFields());

        /** @var Collection<BaseRelationField> $relationFields */
        $relationFields = $customFields->filter(fn($field) => (
            $field instanceof BaseRelationField &&
            $field::elementType() === $this->targetElementType
        ));

        $targetRefHandle = $this->targetElementType::refHandle();
        /** @var Collection<Link> $linkFields */
        $linkFields = $customFields->filter(fn($field) => (
            $field instanceof Link &&
            Collection::make($field->getLinkTypes())->contains(fn(BaseLinkType $linkType) => (
                $linkType instanceof BaseElementLinkType &&
                $linkType::id() === $targetRefHandle
            ))
        ));

        if ($relationFields->isEmpty() && $linkFields->isEmpty()) {
            return;
        }

        /** @var CustomFieldBehavior $behavior */
        $behavior = $item->getBehavior('customFields');
        $saveElement = false;

        foreach ($relationFields as $field) {
            $this->processRelationField($item, $field, $behavior->{$field->handle}, $saveElement);
        }

        foreach ($linkFields as $field) {
            $this->processLinkField($item, $field, $behavior->{$field->handle}, $saveElement);
        }

        if ($saveElement) {
            $item->setScenario(Element::SCENARIO_ESSENTIALS);
            $item->resaving = true;

            try {
                Craft::$app->getElements()->saveElement($item, false, false);
            } catch (Throwable $e) {
                Craft::$app->getErrorHandler()->logException($e);
            }
        }
    }

    private function processRelationField(ElementInterface $item, BaseRelationField $field, mixed $value, bool &$saveElement): void
    {
        // avoid a DB query if we can
        if (!is_array($value)) {
            /** @var ElementQueryInterface $value */
            $value = $item->getFieldValue($field->handle);
            $value = $value
                ->site('*')
                ->unique()
                ->status(null)
                ->drafts(null)
                ->withProvisionalDrafts()
                ->revisions(null)
                ->trashed(null)
                ->ids();
        }

        $value = array_map(fn($id) => (int)$id, array_values(array_filter($value)));

        $newValue = array_values(array_unique(
            array_map(fn($id) => in_array($id, $this->oldTargetIds) ? $this->newTargetId : $id, $value)
        ));

        if ($value !== $newValue) {
            $item->setFieldValue($field->handle, $newValue);
            $saveElement = true;
        }
    }

    private function processLinkField(ElementInterface $item, Link $field, mixed $value, bool &$saveElement): void
    {
        if (empty($value['value']) || !preg_match(Elements::REF_TAG_PATTERN, $value['value'], $matches)) {
            return;
        }

        $elementType = $matches['elementType'];
        $ref = $matches['ref'];
        $siteId = $matches['site'] ?? null;
        $attribute = $matches['attr'] ?? null;

        if (!is_numeric($ref) || !in_array((int)$ref, $this->oldTargetIds)) {
            return;
        }

        $item->setFieldValue($field->handle, [
            'type' => $value['type'],
            'value' => sprintf(
                '{%s:%s%s%s}',
                $elementType,
                $this->newTargetId,
                $siteId ? "@$siteId" : '',
                $attribute ? ":$attribute" : '',
            ),
        ]);
        $saveElement = true;
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): ?string
    {
        return Translation::prep('app', 'Replacing {type} relations', [
            'type' => $this->targetElementType::lowerDisplayName(),
        ]);
    }
}
