<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Operations;

use CraftCms\Cms\Activity\Data\ActivitySubject;
use CraftCms\Cms\Activity\ElementActivity;
use CraftCms\Cms\Activity\StructuralElementActivity;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Events\ElementDeleted;
use CraftCms\Cms\Element\Events\ElementDeletedForSite;
use CraftCms\Cms\Element\Events\ElementDeleting;
use CraftCms\Cms\Element\Events\ElementDeletingForSite;
use CraftCms\Cms\Element\Events\ElementRestored;
use CraftCms\Cms\Element\Events\ElementRestoring;
use CraftCms\Cms\Element\Events\ElementsMerged;
use CraftCms\Cms\Element\Exceptions\UnsupportedSiteException;
use CraftCms\Cms\Element\Queries\Exceptions\ElementNotFoundException;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Field\BaseRelationField;
use CraftCms\Cms\Search\Jobs\FindAndReplace;
use CraftCms\Cms\Search\Search;
use CraftCms\Cms\Structure\Models\StructureElement as StructureElementModel;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Facades\Activities;
use CraftCms\Cms\Support\Facades\BulkOps;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Sites;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;
use Tpetry\QueryExpressions\Language\Alias;

/** @internal */
readonly class ElementDeletions
{
    public function __construct(
        private Elements $elements,
        private ElementWrites $elementWrites,
        private ElementCaches $elementCaches,
        private Search $search,
    ) {}

    public function mergeElementsByIds(int $mergedElementId, int $prevailingElementId): bool
    {
        if (! $mergedElement = $this->elements->getElementById($mergedElementId)) {
            throw new ElementNotFoundException("No element exists with the ID '$mergedElementId'");
        }

        if (! $prevailingElement = $this->elements->getElementById($prevailingElementId)) {
            throw new ElementNotFoundException("No element exists with the ID '$prevailingElementId'");
        }

        return $this->mergeElements($mergedElement, $prevailingElement);
    }

    public function mergeElements(ElementInterface $mergedElement, ElementInterface $prevailingElement): bool
    {
        $mergedSubject = ActivitySubject::fromElement($mergedElement);
        $prevailingSubject = ActivitySubject::fromElement($prevailingElement);

        return DB::transaction(function () use ($mergedElement, $prevailingElement, $mergedSubject, $prevailingSubject) {
            $data = DB::table(Table::RELATIONS, 'r')
                ->select(['r.sourceId', 'r.sourceSiteId', 'e.type'])
                ->join(new Alias(Table::ELEMENTS, 'e'), 'e.id', 'r.sourceId')
                ->where('r.targetId', $mergedElement->id)
                ->get()
                ->groupBy(['type', fn ($relation) => $relation->sourceSiteId ?? '*']);

            foreach ($data as $elementType => $typeData) {
                foreach ($typeData as $siteId => $relations) {
                    /** @var class-string<ElementInterface> $elementType */
                    $query = $elementType::find()
                        ->id($relations->pluck('sourceId'))
                        ->siteId($siteId)
                        ->drafts(null)
                        ->revisions(null)
                        ->trashed(null)
                        ->status(null);

                    if ($siteId === '*') {
                        $query->unique();
                    }

                    $query->cursor()->each(function (ElementInterface $element) use ($prevailingElement, $mergedElement) {
                        foreach ($element->getFieldLayout()?->getCustomFields() ?? [] as $field) {
                            $fieldValue = $element->getCustomFieldRawValue($field->handle);
                            if (
                                $field instanceof BaseRelationField &&
                                is_array($fieldValue) &&
                                in_array($mergedElement->id, $fieldValue)
                            ) {
                                if (in_array($prevailingElement->id, $fieldValue)) {
                                    $value = array_values(array_filter($fieldValue, fn ($value) => $value !== $mergedElement->id));
                                } else {
                                    $value = array_map(fn ($value) => $value === $mergedElement->id ? $prevailingElement->id : $value, $fieldValue);
                                }

                                $element->setFieldValue($field->handle, $value);
                            }
                        }

                        if (! empty($element->getDirtyFields())) {
                            $element->resaving = true;
                            $this->elementWrites->saveElement($element, false);
                        }
                    });
                }
            }

            $relations = DB::table(Table::RELATIONS)
                ->select(['id', 'fieldId', 'sourceId', 'sourceSiteId'])
                ->where('targetId', $mergedElement->id)
                ->get();

            if ($relations->isNotEmpty()) {
                $relationKey = fn (object $relation) => "{$relation->fieldId}:{$relation->sourceId}:{$relation->sourceSiteId}";
                $prevailingRelations = DB::table(Table::RELATIONS)
                    ->select(['fieldId', 'sourceId', 'sourceSiteId'])
                    ->where('targetId', $prevailingElement->id)
                    ->whereIn('fieldId', $relations->pluck('fieldId'))
                    ->whereIn('sourceId', $relations->pluck('sourceId'))
                    ->get()
                    ->keyBy($relationKey);
                $relationIds = $relations
                    ->reject(fn (object $relation) => $prevailingRelations->has($relationKey($relation)))
                    ->pluck('id');

                if ($relationIds->isNotEmpty()) {
                    DB::table(Table::RELATIONS)
                        ->whereIn('id', $relationIds)
                        ->update([
                            'targetId' => $prevailingElement->id,
                            'dateUpdated' => now(),
                        ]);
                }
            }

            $structureElements = DB::table(Table::STRUCTUREELEMENTS)
                ->select(['id', 'structureId'])
                ->where('elementId', $mergedElement->id)
                ->get();

            if ($structureElements->isNotEmpty()) {
                $prevailingStructureIds = DB::table(Table::STRUCTUREELEMENTS)
                    ->where('elementId', $prevailingElement->id)
                    ->whereIn('structureId', $structureElements->pluck('structureId'))
                    ->pluck('structureId', 'structureId');
                $structureElementIds = $structureElements
                    ->reject(fn (object $structureElement) => $prevailingStructureIds->has($structureElement->structureId))
                    ->pluck('id');

                if ($structureElementIds->isNotEmpty()) {
                    DB::table(Table::STRUCTUREELEMENTS)
                        ->whereIn('id', $structureElementIds)
                        ->update([
                            'elementId' => $prevailingElement->id,
                            'dateUpdated' => now(),
                        ]);
                }
            }

            $elementType = $this->elements->getElementTypeById($prevailingElement->id);

            if ($elementType !== null && ($refHandle = $elementType::refHandle()) !== null) {
                $refTagPrefix = '{'.$refHandle.':';

                dispatch(new FindAndReplace(
                    find: $refTagPrefix.$mergedElement->id.':',
                    replace: $refTagPrefix.$prevailingElement->id.':',
                    description: I18N::prep('Updating element references'),
                ))->afterCommit();

                dispatch(new FindAndReplace(
                    find: $refTagPrefix.$mergedElement->id.'}',
                    replace: $refTagPrefix.$prevailingElement->id.'}',
                    description: $refTagPrefix.$prevailingElement->id.'}',
                ))->afterCommit();
            }

            event(new ElementsMerged($mergedElement->id, $prevailingElement->id));

            if (! $this->deleteElement($mergedElement, recordActivity: false)) {
                return false;
            }

            StructuralElementActivity::recordMerged($mergedSubject, $prevailingSubject);

            return true;
        });
    }

    /**
     * @param  class-string<ElementInterface>|null  $elementType
     */
    public function deleteElementById(
        int $elementId,
        ?string $elementType = null,
        ?int $siteId = null,
        bool $hardDelete = false,
    ): bool {
        $elementType ??= $this->elements->getElementTypeById($elementId);

        if ($elementType === null) {
            return false;
        }

        if ($siteId === null && $elementType::isLocalized() && Sites::isMultiSite()) {
            $siteId = (int) DB::table(Table::ELEMENTS_SITES)
                ->where('elementId', $elementId)
                ->value('siteId');

            if ($siteId === 0) {
                return false;
            }
        }

        $element = $this->elements->getElementById($elementId, $elementType, $siteId);

        if (! $element) {
            return false;
        }

        return $this->deleteElement($element, $hardDelete);
    }

    public function deleteElement(
        ElementInterface $element,
        bool $hardDelete = false,
        bool $recordActivity = true,
    ): bool {
        event($event = new ElementDeleting($element, $hardDelete));

        $element->hardDelete = $hardDelete || $event->hardDelete;

        if (! $event->isValid) {
            return false;
        }

        if (! $element->beforeDelete()) {
            return false;
        }

        $recordActivity = $recordActivity &&
            $this->shouldRecordLifecycleActivity($element);

        return BulkOps::ensure(function () use ($element, $recordActivity) {
            DB::beginTransaction();
            DateTimeHelper::pause();

            try {
                $elementRecord = DB::table(Table::ELEMENTS)
                    ->select('dateDeleted')
                    ->where('id', $element->id)
                    ->lockForUpdate()
                    ->first();

                if ($elementRecord === null) {
                    DB::rollBack();

                    return false;
                }

                if (! $element->hardDelete && $elementRecord->dateDeleted !== null) {
                    DB::commit();

                    return true;
                }

                while (($record = StructureElementModel::where('elementId', $element->id)->first()) !== null) {
                    while (($child = $record->children(1)->first()) !== null) {
                        /** @var StructureElementModel $child */
                        $child->insertBefore($record);
                        $record->refresh();
                    }
                    $record->deleteWithChildren();
                }

                $this->elementCaches->invalidateForElement($element);

                if ($element->hardDelete) {
                    DB::table(Table::ELEMENTS)->delete($element->id);
                    DB::table(Table::SEARCHINDEX)
                        ->where('elementId', $element->id)
                        ->delete();
                } else {
                    DB::table(Table::ELEMENTS)
                        ->where('id', $element->id)
                        ->update([
                            'dateUpdated' => $now = now(),
                            'dateDeleted' => $now,
                            'deletedWithOwner' => $element->deletedWithOwner,
                        ]);

                    $this->setDraftAndRevisionDeletionState($element->id);
                }

                $element->dateDeleted = now();
                $element->afterDelete();

                if ($recordActivity) {
                    Activities::record(
                        $element->hardDelete ? 'craft.element.deleted' : 'craft.element.trashed',
                        subject: $element,
                        site: Sites::getSiteById($element->siteId),
                    );
                }

                if (! $element->hardDelete) {
                    BulkOps::trackElement($element);
                }

                DB::commit();
            } catch (Throwable $throwable) {
                DB::rollBack();

                throw $throwable;
            } finally {
                DateTimeHelper::resume();
            }

            event(new ElementDeleted($element));

            return true;
        });
    }

    public function deleteElementForSite(ElementInterface $element): void
    {
        $this->deleteElementsForSite([$element]);
    }

    /**
     * @param  ElementInterface[]  $elements
     */
    public function deleteElementsForSite(array $elements): void
    {
        if (empty($elements)) {
            return;
        }

        $firstElement = reset($elements);
        $elementType = $firstElement::class;

        foreach ($elements as $element) {
            if ($element::class !== $elementType || $element->siteId !== $firstElement->siteId) {
                throw new InvalidArgumentException('All elements must have the same type and site ID.');
            }
        }

        DB::transaction(function () use ($elements, $firstElement) {
            $siteElementIds = DB::table(Table::ELEMENTS_SITES)
                ->whereIn('elementId', Arr::pluck($elements, 'id'))
                ->where('siteId', $firstElement->siteId)
                ->lockForUpdate()
                ->pluck('elementId')
                ->flip();

            $elements = array_filter(
                $elements,
                fn (ElementInterface $element) => $siteElementIds->has($element->id),
            );

            if ($elements === []) {
                return;
            }

            $multiSiteElementIds = $firstElement::find()
                ->id(Arr::pluck($elements, 'id'))
                ->status(null)
                ->drafts(null)
                ->siteId(['not', $firstElement->siteId])
                ->unique()
                ->pluck('elements.id')
                ->all();

            $multiSiteElementIdsIdx = array_flip($multiSiteElementIds);
            $multiSiteElements = [];
            $singleSiteElements = [];

            foreach ($elements as $element) {
                if (isset($multiSiteElementIdsIdx[$element->id])) {
                    $multiSiteElements[] = $element;
                } else {
                    $singleSiteElements[] = $element;
                }
            }

            if (! empty($multiSiteElements)) {
                foreach ($multiSiteElements as $element) {
                    event(new ElementDeletingForSite($element));
                }

                foreach ($multiSiteElements as $element) {
                    $element->beforeDeleteForSite();
                }

                DB::table(Table::ELEMENTS_SITES)
                    ->whereIn('elementId', $multiSiteElementIds)
                    ->where('siteId', $firstElement->siteId)
                    ->delete();

                $this->elementWrites->resaveElements(
                    query: $firstElement::find()
                        ->id($multiSiteElementIds)
                        ->status(null)
                        ->drafts(null)
                        ->site('*')
                        ->unique(),
                    continueOnError: true,
                    updateSearchIndex: false,
                );

                foreach ($multiSiteElements as $element) {
                    $element->afterDeleteForSite();

                    if ($this->shouldRecordLifecycleActivity($element)) {
                        Activities::record(
                            'craft.element.site-removed',
                            subject: $element,
                            site: Sites::getSiteById($element->siteId),
                        );
                    }
                }

                foreach ($multiSiteElements as $element) {
                    event(new ElementDeletedForSite($element));
                }
            }

            foreach ($singleSiteElements as $element) {
                $this->deleteElement($element, true);
            }
        });
    }

    public function restoreElement(ElementInterface $element): bool
    {
        return $this->restoreElements([$element]);
    }

    /**
     * @param  ElementInterface[]  $elements
     */
    public function restoreElements(array $elements): bool
    {
        foreach ($elements as $element) {
            event(new ElementRestoring($element));

            if (! $element->beforeRestore()) {
                return false;
            }
        }

        DB::beginTransaction();

        try {
            $recordActivity = [];
            $elementStates = DB::table(Table::ELEMENTS)
                ->whereIn('id', Arr::pluck($elements, 'id'))
                ->lockForUpdate()
                ->pluck('dateDeleted', 'id');

            foreach ($elements as $element) {
                if (! $elementStates->has($element->id) && $element->uid !== null) {
                    DB::rollBack();

                    return false;
                }

                $recordActivity[spl_object_id($element)] = $this->shouldRecordLifecycleActivity($element) &&
                    $elementStates->get($element->id) !== null;
            }

            /** @var Element $element */
            foreach ($elements as $element) {
                $supportedSites = Arr::keyBy(ElementHelper::supportedSitesForElement($element), 'siteId');
                if (empty($supportedSites)) {
                    throw new UnsupportedSiteException($element, $element->siteId,
                        "Element $element->id has no supported sites.");
                }

                if (! isset($supportedSites[$element->siteId])) {
                    throw new UnsupportedSiteException($element, $element->siteId,
                        'Attempting to restore an element in an unsupported site.');
                }

                $otherSiteIds = array_keys(Arr::except($supportedSites, $element->siteId));

                if (! empty($otherSiteIds)) {
                    $siteElements = $element->getLocalizedQuery()
                        ->siteId($otherSiteIds)
                        ->status(null)
                        ->trashed(null)
                        ->all();
                } else {
                    $siteElements = [];
                }

                $element->ruleset->useScenario(ElementRules::SCENARIO_ESSENTIALS);
                if (! $element->validate()) {
                    Log::warning("Unable to restore element $element->id: doesn't pass essential validation: ".print_r($element->errors()->all(), true), [__METHOD__]);
                    DB::rollBack();

                    return false;
                }

                foreach ($siteElements as $siteElement) {
                    if ($siteElement === $element) {
                        continue;
                    }

                    $siteElement->ruleset->useScenario(ElementRules::SCENARIO_ESSENTIALS);

                    if (! $siteElement->validate()) {
                        Log::warning("Unable to restore element $element->id: doesn't pass essential validation for site $element->siteId: ".print_r($element->errors()->all(), true), [__METHOD__]);
                        throw new Exception("Element $element->id doesn't pass essential validation for site $element->siteId.");
                    }
                }

                DB::table(Table::ELEMENTS)
                    ->where('id', $element->id)
                    ->update([
                        'dateDeleted' => null,
                        'dateUpdated' => now(),
                        'deletedWithOwner' => null,
                    ]);

                $this->setDraftAndRevisionDeletionState($element->id, false);

                $this->search->indexElementAttributes($element);
                foreach ($siteElements as $siteElement) {
                    $this->search->indexElementAttributes($siteElement);
                }

                $this->elementCaches->invalidateForElement($element);
            }

            foreach ($elements as $element) {
                $element->afterRestore();
                $element->trashed = false;
                $element->dateDeleted = null;
                $element->deletedWithOwner = null;

                if ($recordActivity[spl_object_id($element)]) {
                    Activities::record(
                        'craft.element.restored',
                        subject: $element,
                        site: Sites::getSiteById($element->siteId),
                    );
                }

                event(new ElementRestored($element));
            }

            DB::commit();
        } catch (Throwable $throwable) {
            DB::rollBack();

            throw $throwable;
        }

        return true;
    }

    private function shouldRecordLifecycleActivity(ElementInterface $element): bool
    {
        return $element->uid !== null &&
            ElementActivity::shouldRecord($element);
    }

    private function setDraftAndRevisionDeletionState(int $canonicalId, bool $delete = true): void
    {
        foreach (['draftId' => Table::DRAFTS, 'revisionId' => Table::REVISIONS] as $foreignKey => $table) {
            DB::table(new Alias(Table::ELEMENTS, 'e'))
                ->whereIn(
                    "e.$foreignKey",
                    DB::table(new Alias($table, 't'))
                        ->select('t.id')
                        ->where('t.canonicalId', $canonicalId),
                )
                ->update([
                    'dateDeleted' => $delete ? now() : null,
                ]);
        }
    }
}
