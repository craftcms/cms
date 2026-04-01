<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Actions;

use craft\base\ElementInterface;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Exceptions\FieldNotFoundException;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/** @internal */
readonly class UpdateCanonicalElementAction
{
    public function __construct(
        private Elements $elements,
    ) {}

    /**
     * Updates the canonical element from a given derivative, such as a draft or revision.
     *
     * @template T of ElementInterface
     *
     * @param  T  $element  The derivative element
     * @param  array  $newAttributes  Any attributes to apply to the canonical element
     * @return T The updated canonical element
     *
     * @throws InvalidArgumentException if the element is already a canonical element
     */
    public function handle(ElementInterface $element, array $newAttributes = []): ElementInterface
    {
        if ($element->getIsCanonical()) {
            throw new InvalidArgumentException('Element was already canonical');
        }

        // we need to check if the entry type is still available for this element's section
        /** @phpstan-ignore-next-line */
        if ($element->hasMethod('isEntryTypeCompatible') && ! $element->isEntryTypeCompatible()) {
            throw new InvalidArgumentException('Entry Type is no longer allowed in this section.');
        }

        // "Duplicate" the derivative element with the canonical element’s ID and UID
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

        // if we're working with a revision, ensure we mark element's custom fields as dirty;
        if ($element->getIsRevision()) {
            $newAttributes['dirtyFields'] = array_map(
                fn (FieldInterface $field) => $field->handle,
                $element->getFieldLayout()?->getCustomFields() ?? [],
            );
        }

        $updatedCanonical = $this->elements->duplicateElement($element, $newAttributes);

        app()->terminating(function () use (
            $canonical,
            $updatedCanonical,
            $changedAttributes,
            $changedFields
        ) {
            // Update change tracking for the canonical element
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
