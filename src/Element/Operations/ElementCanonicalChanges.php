<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Operations;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\BulkOp\BulkOps;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Events\AfterMergeCanonicalChanges;
use CraftCms\Cms\Element\Events\BeforeMergeCanonicalChanges;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Exceptions\FieldNotFoundException;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\DateTimeHelper;
use Exception;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/** @internal */
readonly class ElementCanonicalChanges
{
    public function __construct(
        private BulkOps $bulkOps,
        private ElementWrites $elementWrites,
        private ElementDuplicates $elementDuplicates,
    ) {}

    public function mergeCanonicalChanges(ElementInterface $element): void
    {
        if ($element->getIsCanonical()) {
            throw new InvalidArgumentException('Only a derivative element can be passed to '.__METHOD__);
        }

        if (! $element::trackChanges()) {
            throw new InvalidArgumentException($element::class.' elements don’t track their changes');
        }

        $supportedSites = Arr::keyBy(ElementHelper::supportedSitesForElement($element), 'siteId');
        if (! isset($supportedSites[$element->siteId])) {
            throw new Exception('Attempting to merge source changes for a draft in an unsupported site.');
        }

        event(new BeforeMergeCanonicalChanges($element));

        $this->bulkOps->ensure(function () use ($element, $supportedSites) {
            DB::transaction(function () use ($element, $supportedSites) {
                $otherSiteIds = array_keys(Arr::except($supportedSites, $element->siteId));
                if (! empty($otherSiteIds)) {
                    $siteElements = $element->getLocalizedQuery()
                        ->siteId($otherSiteIds)
                        ->status(null)
                        ->all();
                } else {
                    $siteElements = [];
                }

                foreach ($siteElements as $siteElement) {
                    $siteElement->mergeCanonicalChanges();
                    $siteElement->mergingCanonicalChanges = true;
                    $this->elementWrites->save(
                        element: $siteElement,
                        runValidation: false,
                        propagate: false,
                        supportedSites: $supportedSites,
                    );
                }

                $element->mergeCanonicalChanges();
                $duplicateOf = $element->duplicateOf;
                $element->duplicateOf = null;
                $element->dateLastMerged = DateTimeHelper::now();
                $element->mergingCanonicalChanges = true;
                $this->elementWrites->save(
                    element: $element,
                    runValidation: false,
                    propagate: false,
                    supportedSites: $supportedSites,
                );
                $element->duplicateOf = $duplicateOf;

                $element->afterPropagate(false);
            });

            $element->mergingCanonicalChanges = false;
        });

        event(new AfterMergeCanonicalChanges($element));
    }

    public function updateCanonicalElement(ElementInterface $element, array $newAttributes = []): ElementInterface
    {
        if ($element->getIsCanonical()) {
            throw new InvalidArgumentException('Element was already canonical');
        }

        /** @phpstan-ignore-next-line */
        if ($element->hasMethod('isEntryTypeCompatible') && ! $element->isEntryTypeCompatible()) {
            throw new InvalidArgumentException('Entry Type is no longer allowed in this section.');
        }

        $canonical = $element->getCanonical();

        $changedAttributes = DB::table(Table::CHANGEDATTRIBUTES)
            ->select(['siteId', 'attribute', 'propagated', 'userId'])
            ->where('elementId', $element->id)
            ->get();

        $changedFields = DB::table(Table::CHANGEDFIELDS)
            ->select(['siteId', 'fieldId', 'layoutElementUid', 'propagated', 'userId'])
            ->where('elementId', $element->id)
            ->get();

        $newAttributes += [
            'id' => $canonical->id,
            'uid' => $canonical->uid,
            'canonicalId' => $canonical->getCanonicalId(),
            'root' => $canonical->root,
            'lft' => $canonical->lft,
            'rgt' => $canonical->rgt,
            'level' => $canonical->level,
            'dateCreated' => $canonical->dateCreated,
            'dateDeleted' => null,
            'draftId' => null,
            'revisionId' => null,
            'isProvisionalDraft' => false,
            'updatingFromDerivative' => true,
            'dirtyAttributes' => [],
            'dirtyFields' => [],
        ];

        if ($canonical instanceof Entry) {
            $newAttributes['oldStatus'] = $canonical->oldStatus;
        }

        foreach ($changedAttributes as $attribute) {
            $newAttributes['siteAttributes'][$attribute->siteId]['dirtyAttributes'][] = $attribute->attribute;
        }

        foreach ($changedFields as $changedField) {
            $layoutElement = $element->getFieldLayout()?->getElementByUid($changedField->layoutElementUid);
            if ($layoutElement instanceof CustomField) {
                try {
                    $field = $layoutElement->getField();
                } catch (FieldNotFoundException) {
                    continue;
                }
                $newAttributes['siteAttributes'][$changedField->siteId]['dirtyFields'][] = $field->handle;
            }
        }

        if ($element->getIsRevision()) {
            $newAttributes['dirtyFields'] = array_map(
                fn (FieldInterface $field) => $field->handle,
                $element->getFieldLayout()?->getCustomFields() ?? [],
            );
        }

        $updatedCanonical = $this->elementDuplicates->duplicateElement($element, $newAttributes);

        app()->terminating(function () use (
            $canonical,
            $updatedCanonical,
            $changedAttributes,
            $changedFields
        ) {
            foreach ($changedAttributes as $attribute) {
                DB::table(Table::CHANGEDATTRIBUTES)
                    ->upsert([
                        'elementId' => $canonical->id,
                        'siteId' => $attribute->siteId,
                        'attribute' => $attribute->attribute,
                        'dateUpdated' => $updatedCanonical->dateUpdated,
                        'propagated' => $attribute->propagated,
                        'userId' => $attribute->userId,
                    ], ['elementId', 'siteId', 'attribute']);
            }

            foreach ($changedFields as $field) {
                DB::table(Table::CHANGEDFIELDS)
                    ->upsert([
                        'elementId' => $canonical->id,
                        'siteId' => $field->siteId,
                        'fieldId' => $field->fieldId,
                        'layoutElementUid' => $field->layoutElementUid,
                        'dateUpdated' => $updatedCanonical->dateUpdated,
                        'propagated' => $field->propagated,
                        'userId' => $field->userId,
                    ], ['elementId', 'siteId', 'fieldId', 'layoutElementUid']);
            }
        });

        return $updatedCanonical;
    }
}
