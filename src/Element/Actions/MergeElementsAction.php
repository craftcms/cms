<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Actions;

use craft\base\ElementInterface;
use craft\behaviors\CustomFieldBehavior;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\ElementTypes;
use CraftCms\Cms\Element\Events\AfterMergeElements;
use CraftCms\Cms\Field\BaseRelationField;
use CraftCms\Cms\Search\Jobs\FindAndReplace;
use CraftCms\Cms\Support\Facades\I18N;
use Illuminate\Support\Facades\DB;
use Tpetry\QueryExpressions\Language\Alias;

/** @internal */
readonly class MergeElementsAction
{
    public function __construct(
        private Elements $elements,
        private ElementTypes $elementTypes,
    ) {}

    public function handle(ElementInterface $mergedElement, ElementInterface $prevailingElement): bool
    {
        return DB::transaction(function () use ($mergedElement, $prevailingElement) {
            // Find elements that relate to the merged element
            $data = DB::table(Table::RELATIONS, 'r')
                ->select(['r.sourceId', 'r.sourceSiteId', 'e.type'])
                ->join(new Alias(Table::ELEMENTS, 'e'), 'e.id', 'r.sourceId')
                ->where('r.targetId', $mergedElement->id)
                ->get()
                ->groupBy(['type', fn ($r) => $r->sourceSiteId ?? '*']);

            foreach ($data as $elementType => $typeData) {
                foreach ($typeData as $siteId => $relations) {
                    /** @var class-string<ElementInterface> $elementType */
                    /** @var ElementCollection $relations */
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

                    $query->each(function (ElementInterface $element) use ($prevailingElement, $mergedElement) {
                        /** @var ElementInterface $element */
                        /** @var CustomFieldBehavior $behavior */
                        $behavior = $element->getBehavior('customFields');
                        foreach ($element->getFieldLayout()?->getCustomFields() ?? [] as $field) {
                            if (
                                $field instanceof BaseRelationField &&
                                isset($behavior->{$field->handle}) &&
                                is_array($behavior->{$field->handle}) &&
                                in_array($mergedElement->id, $behavior->{$field->handle})
                            ) {
                                // see if the prevailing element is related too
                                if (in_array($prevailingElement->id, $behavior->{$field->handle})) {
                                    $value = array_values(array_filter($behavior->{$field->handle}, fn ($v) => $v !== $mergedElement->id));
                                } else {
                                    $value = array_map(fn ($v) => $v === $mergedElement->id ? $prevailingElement->id : $v, $behavior->{$field->handle});
                                }
                                $element->setFieldValue($field->handle, $value);
                            }
                        }

                        if (! empty($element->getDirtyFields())) {
                            $element->resaving = true;
                            $this->elements->saveElement($element, false);
                        }
                    });
                }
            }

            // Deal with any remaining relation values
            // (Not all relation field values have been saved since 5.3.0 when relation fields
            // started saving the target element IDs in the content JSON.)
            $relations = DB::table(Table::RELATIONS)
                ->select(['id', 'fieldId', 'sourceId', 'sourceSiteId'])
                ->where('targetId', $mergedElement->id)
                ->get();

            foreach ($relations as $relation) {
                // Make sure the persisting element isn't already selected in the same field
                $persistingElementIsRelatedToo = DB::table(Table::RELATIONS)
                    ->where('fieldId', $relation->fieldId)
                    ->where('sourceId', $relation->sourceId)
                    ->where('sourceSiteId', $relation->sourceSiteId)
                    ->where('targetId', $prevailingElement->id)
                    ->exists();

                if (! $persistingElementIsRelatedToo) {
                    DB::table(Table::RELATIONS)
                        ->where('id', $relation->id)
                        ->update([
                            'targetId' => $prevailingElement->id,
                            'dateUpdated' => now(),
                        ]);
                }
            }

            // Update any structures that the merged element is in
            $structureElements = DB::table(Table::STRUCTUREELEMENTS)
                ->select(['id', 'structureId'])
                ->where('elementId', $mergedElement->id)
                ->get();

            foreach ($structureElements as $structureElement) {
                // Make sure the persisting element isn't already a part of that structure
                $persistingElementIsInStructureToo = DB::table(Table::STRUCTUREELEMENTS)
                    ->where('structureId', $structureElement->structureId)
                    ->where('elementId', $prevailingElement->id)
                    ->exists();

                if (! $persistingElementIsInStructureToo) {
                    DB::table(Table::STRUCTUREELEMENTS)
                        ->where('id', $structureElement->id)
                        ->update([
                            'elementId' => $prevailingElement->id,
                            'dateUpdated' => now(),
                        ]);
                }
            }

            // Update any reference tags
            $elementType = $this->elementTypes->getElementTypeById($prevailingElement->id);

            if ($elementType !== null && ($refHandle = $elementType::refHandle()) !== null) {
                $refTagPrefix = '{'.$refHandle.':';

                dispatch(new FindAndReplace(
                    find: $refTagPrefix.$mergedElement->id.':',
                    replace: $refTagPrefix.$prevailingElement->id.':',
                    description: I18N::prep('Updating element references'),
                ));

                dispatch(new FindAndReplace(
                    find: $refTagPrefix.$mergedElement->id.'}',
                    replace: $refTagPrefix.$prevailingElement->id.'}',
                    description: $refTagPrefix.$prevailingElement->id.'}',
                ));
            }

            event(new AfterMergeElements($mergedElement->id, $prevailingElement->id));

            // Now delete the merged element
            return $this->elements->deleteElement($mergedElement);
        });
    }
}
