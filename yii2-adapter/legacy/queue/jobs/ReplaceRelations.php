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
use craft\i18n\Translation;
use craft\queue\BaseBatchedElementJob;
use Illuminate\Support\Collection;
use Throwable;

/**
 * ReplaceRelations job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.10.0
 * @deprecated in Craft 6.0.0. Use {@see \CraftCms\Cms\Element\Jobs\ReplaceRelations} instead.
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
        /** @var BaseRelationField[] $fields */
        $fields = Collection::make($item->getFieldLayout()?->getCustomFields())
            ->filter(fn($field) => (
                $field instanceof BaseRelationField &&
                $field::elementType() === $this->targetElementType
            ));

        if (empty($fields)) {
            return;
        }

        /** @var CustomFieldBehavior $behavior */
        $behavior = $item->getBehavior('customFields');
        $saveElement = false;

        foreach ($fields as $field) {
            // avoid a DB query if we can
            $value = $behavior->{$field->handle};

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
