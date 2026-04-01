<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Actions;

use craft\base\ElementInterface;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Element\Exceptions\UnsupportedSiteException;
use CraftCms\Cms\Shared\Exceptions\OperationAbortedException;
use CraftCms\Cms\Structure\Enums\Mode;
use CraftCms\Cms\Structure\Structures;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\BulkOps;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Typecast;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;
use UnitEnum;

use function CraftCms\Cms\t;

/** @internal */
readonly class DuplicateElementAction
{
    public function __construct(
        private Elements $elements,
        private Drafts $drafts,
        private PropagateElementAction $propagateElementAction,
        private SaveElementAction $saveElementAction,
        private Structures $structures,
    ) {}

    /**
     * Duplicates an element.
     *
     * @template T of ElementInterface
     *
     * @param  T  $element  the element to duplicate
     * @param  array  $newAttributes  any attributes to apply to the duplicate. This can contain a `siteAttributes` key,
     *                                set to an array of site-specific attribute array, indexed by site IDs.
     * @param  bool  $placeInStructure  whether to position the cloned element after the original one in its structure.
     *                                  (This will only happen if the duplicated element is canonical.)
     * @param  bool  $asUnpublishedDraft  whether the duplicate should be created as unpublished draft
     * @param  bool  $checkAuthorization  whether to ensure the current user is authorized to save the new element,
     *                                    once its new attributes have been applied to it
     * @param  bool  $copyModifiedFields  whether to copy modified attribute/field data over to the duplicated element
     * @return T the duplicated element
     *
     * @throws UnsupportedSiteException if the element is being duplicated into a site it doesn’t support
     * @throws InvalidElementException if saveElement() returns false for any of the sites
     * @throws HttpException if the user isn't authorized to save the duplicated element
     * @throws Throwable if reasons
     */
    public function handle(
        ElementInterface $element,
        array $newAttributes = [],
        bool $placeInStructure = true,
        bool $asUnpublishedDraft = false,
        bool $checkAuthorization = false,
        bool $copyModifiedFields = false,
    ): ElementInterface {
        // Make sure the element exists
        if (! $element->id) {
            throw new Exception('Attempting to duplicate an unsaved element.');
        }

        // Ensure all fields have been normalized
        $element->getFieldValues();

        // Create our first clone for the $element’s site
        $mainClone = clone $element;
        $mainClone->id = null;
        $mainClone->uid = Str::uuid()->toString();
        $mainClone->draftId = null;
        $mainClone->siteSettingsId = null;
        $mainClone->root = null;
        $mainClone->lft = null;
        $mainClone->rgt = null;
        $mainClone->level = null;
        $mainClone->dateCreated = null;
        $mainClone->dateUpdated = null;
        $mainClone->dateLastMerged = null;
        $mainClone->duplicateOf = $element;
        $mainClone->setCanonicalId(null);

        Arr::pull($newAttributes, 'behaviors', []);
        $mainClone->setRevisionNotes(Arr::pull($newAttributes, 'revisionNotes'));

        // Extract any attributes that are meant for other sites
        $siteAttributes = Arr::pull($newAttributes, 'siteAttributes', []);

        // Note: must use Craft::configure() rather than setAttributes() here,
        // so we're not limited to whatever attributes() returns
        Typecast::configure($mainClone, Arr::merge(
            $newAttributes,
            $siteAttributes[$mainClone->siteId] ?? [],
        ));

        // Make sure the element actually supports its own site ID
        $supportedSites = Arr::keyBy(ElementHelper::supportedSitesForElement($mainClone), 'siteId');
        if (! isset($supportedSites[$mainClone->siteId])) {
            throw new UnsupportedSiteException($element, $mainClone->siteId,
                'Attempting to duplicate an element in an unsupported site.');
        }

        // Clone any field values that are objects (without affecting the dirty fields)
        $dirtyFields = $mainClone->getDirtyFields();
        foreach ($mainClone->getFieldValues() as $handle => $value) {
            if (is_object($value) && ! $value instanceof UnitEnum) {
                $mainClone->setFieldValue($handle, clone $value);
            }
        }
        $mainClone->setDirtyFields($dirtyFields, false);

        // Check authorization?
        if ($checkAuthorization && ! ($this->elements->canDuplicate($mainClone) && $this->elements->canSave($mainClone))) {
            abort(403, 'User not authorized to duplicate this element.');
        }

        // If we are duplicating a draft as another draft, create a new draft row
        if ($mainClone->draftId && $mainClone->draftId === $element->draftId) {
            // Are we duplicating a draft of a published element?
            if ($element->getIsDerivative()) {
                $mainClone->draftName = $this->drafts->generateDraftName($element->getCanonicalId());
            } else {
                $mainClone->draftName = t('First draft');
            }
            $mainClone->draftNotes = null;
            $mainClone->setCanonicalId($element->getCanonicalId());
            $mainClone->draftId = $this->drafts->insertDraftRow(
                name: $mainClone->draftName,
                creatorId: Auth::user()->id,
                canonicalId: $element->getCanonicalId(),
                trackChanges: $mainClone->trackDraftChanges,
            );
        }

        // If we are supposed to save it as new unpublished draft
        if ($asUnpublishedDraft) {
            $mainClone->draftName = t('First draft');
            $mainClone->draftNotes = null;
            $mainClone->setCanonicalId(null);
            $mainClone->draftId = $this->drafts->insertDraftRow(
                name: $mainClone->draftName,
                creatorId: Auth::user()->id,
                trackChanges: $mainClone->trackDraftChanges,
            );
        }

        // Validate
        $mainClone->setScenario(Element::SCENARIO_ESSENTIALS);
        $mainClone->validate();

        // If there are any errors on the URI, re-validate as disabled
        if ($mainClone->errors()->has('uri') && $mainClone->enabled) {
            $mainClone->enabled = false;
            $mainClone->validate();
        }

        if ($mainClone->errors()->isNotEmpty()) {
            throw new InvalidElementException($mainClone,
                'Element '.$element->id.' could not be duplicated because it doesn\'t validate.');
        }

        BulkOps::ensure(function () use (
            $mainClone,
            $supportedSites,
            $element,
            $copyModifiedFields,
            $placeInStructure,
            $newAttributes,
            $siteAttributes,
            $asUnpublishedDraft,
        ) {
            DB::beginTransaction();
            try {
                // Start with $element’s site
                if (! $this->saveElementAction->handle($mainClone, false, false, null, $supportedSites, saveContent: true)) {
                    throw new InvalidElementException($mainClone,
                        'Element '.$element->id.' could not be duplicated for site '.$element->siteId);
                }

                if ($copyModifiedFields) {
                    $this->copyModifiedFields($element, $mainClone);
                }

                // Should we add the clone to the source element’s structure?
                if (
                    $placeInStructure &&
                    $mainClone->getIsCanonical() &&
                    ! $mainClone->root &&
                    (! $mainClone->structureId || ! $element->structureId || $mainClone->structureId === $element->structureId)
                ) {
                    $canonical = $element->getCanonical(true);
                    if ($canonical->structureId && $canonical->root) {
                        $mode = isset($newAttributes['id']) ? Mode::Auto : Mode::Insert;
                        $this->structures->moveAfter($canonical->structureId, $mainClone, $canonical, $mode);
                    }
                }

                $propagatedTo = [$mainClone->siteId => true];
                $mainClone->newSiteIds = [];

                // Propagate it
                $otherSiteIds = array_keys(Arr::except($supportedSites, $mainClone->siteId));
                if ($element->id && ! empty($otherSiteIds)) {
                    $siteElements = $element->getLocalizedQuery()
                        ->siteId($otherSiteIds)
                        ->status(null)
                        ->all();

                    foreach ($siteElements as $siteElement) {
                        // Ensure all fields have been normalized
                        $siteElement->getFieldValues();

                        $siteClone = clone $siteElement;
                        $siteClone->duplicateOf = $siteElement;
                        $siteClone->propagating = true;
                        $siteClone->propagatingFrom = $mainClone;
                        $siteClone->id = $mainClone->id;
                        $siteClone->uid = $mainClone->uid;
                        $siteClone->structureId = $mainClone->structureId;
                        $siteClone->root = $mainClone->root;
                        $siteClone->lft = $mainClone->lft;
                        $siteClone->rgt = $mainClone->rgt;
                        $siteClone->level = $mainClone->level;
                        $siteClone->enabled = $mainClone->enabled;
                        $siteClone->siteSettingsId = null;
                        $siteClone->dateCreated = $mainClone->dateCreated;
                        $siteClone->dateUpdated = $mainClone->dateUpdated;
                        $siteClone->dateLastMerged = null;
                        $siteClone->setCanonicalId(null);

                        // Note: must use Typecast::configure() rather than setAttributes() here,
                        // so we're not limited to whatever attributes() returns
                        Typecast::configure($siteClone, Arr::merge(
                            $newAttributes,
                            $siteAttributes[$siteElement->siteId] ?? [],
                        ));
                        $siteClone->siteId = $siteElement->siteId;

                        // Clone any field values that are objects (without affecting the dirty fields)
                        $dirtyFields = $siteClone->getDirtyFields();
                        foreach ($siteClone->getFieldValues() as $handle => $value) {
                            if (is_object($value) && ! $value instanceof UnitEnum) {
                                $siteClone->setFieldValue($handle, clone $value);
                            }
                        }
                        $siteClone->setDirtyFields($dirtyFields, false);

                        if ($element::hasUris()) {
                            // Make sure it has a valid slug
                            $siteClone->validate(['slug']);

                            if ($siteClone->errors()->has('slug')) {
                                throw new InvalidElementException($siteClone,
                                    "Element $element->id could not be duplicated for site $siteElement->siteId: ".$siteClone->errors()->first('slug'));
                            }

                            // Set a unique URI on the site clone
                            try {
                                $this->elements->setElementUri($siteClone);
                            } catch (OperationAbortedException) {
                                // Oh well, not worth bailing over
                            }
                        }

                        if (! $this->saveElementAction->handle($siteClone, false, false, supportedSites: $supportedSites,
                            saveContent: true)) {
                            throw new InvalidElementException($siteClone,
                                "Element $element->id could not be duplicated for site $siteElement->siteId: ".implode(', ',
                                    $siteClone->getFirstErrors()));
                        }

                        if ($copyModifiedFields) {
                            $this->copyModifiedFields($siteElement, $siteClone);
                        }

                        $propagatedTo[$siteClone->siteId] = true;
                        if ($siteClone->isNewForSite) {
                            $mainClone->newSiteIds[] = $siteClone->siteId;
                        }
                    }

                    // Now propagate $mainClone to any sites the source element didn’t already exist in
                    foreach ($supportedSites as $siteId => $siteInfo) {
                        if (! isset($propagatedTo[$siteId]) && $siteInfo['propagate']) {
                            $siteClone = $element->getIsDraft() && ! $element->getIsUnpublishedDraft() ? null : false;
                            if (! $this->propagateElementAction->handle($mainClone, $supportedSites, $siteId, $siteClone)) {
                                /** @phpstan-ignore-next-line */
                                throw $siteClone
                                    ? new InvalidElementException($siteClone,
                                        "Element $siteClone->id could not be propagated to site $siteId: ".implode(', ',
                                            $siteClone->getFirstErrors()))
                                    : new InvalidElementException($mainClone,
                                        "Element $mainClone->id could not be propagated to site $siteId.");
                            }
                            $propagatedTo[$siteId] = true;
                            $mainClone->newSiteIds[] = $siteId;
                        }
                    }
                }

                // It's now fully duplicated and propagated
                $mainClone->afterPropagate(empty($newAttributes['id']));

                DB::commit();
            } catch (Throwable $e) {
                DB::rollBack();
                throw $e;
            }

            // Clean up our tracks
            $mainClone->duplicateOf = null;

            // discard draft from the original element, if it was a provisional draft
            if ($asUnpublishedDraft && $element->isProvisionalDraft) {
                $this->elements->deleteElementById($element->id);
            }
        });

        return $mainClone;
    }

    private function copyModifiedFields(ElementInterface $from, ElementInterface $to): void
    {
        $modifiedAttributes = [
            ...$from->getModifiedAttributes(),
            ...$from->getDirtyAttributes(),
        ];
        $modifiedFields = [
            ...$from->getModifiedFields(),
            ...$from->getDirtyFields(),
        ];

        if ($from->duplicateOf?->getIsDraft()) {
            $modifiedAttributes += [
                ...$from->duplicateOf->getModifiedAttributes(),
                ...$from->duplicateOf->getDirtyAttributes(),
            ];
            $modifiedFields += [
                ...$from->duplicateOf->getModifiedFields(),
                ...$from->duplicateOf->getDirtyFields(),
            ];
        }

        $modifiedAttributes = array_unique($modifiedAttributes);
        $modifiedFields = array_unique($modifiedFields);

        $userId = Auth::user()?->id;

        if (! empty($modifiedAttributes)) {
            $data = [];

            foreach ($modifiedAttributes as $attribute) {
                $data[] = [
                    'elementId' => $to->id,
                    'siteId' => $to->siteId,
                    'attribute' => $attribute,
                    'dateUpdated' => $to->dateUpdated,
                    'propagated' => false,
                    'userId' => $userId,
                ];
            }

            DB::table(Table::CHANGEDATTRIBUTES)->insert($data);
        }

        if (! empty($modifiedFields)) {
            $data = [];
            $fieldLayout = $to->getFieldLayout();

            foreach ($modifiedFields as $handle) {
                $field = $fieldLayout->getFieldByHandle($handle);
                if ($field) {
                    $data[] = [
                        'elementId' => $to->id,
                        'siteId' => $to->siteId,
                        'fieldId' => $field->id,
                        'layoutElementUid' => $field->layoutElement->uid,
                        'dateUpdated' => $to->dateUpdated,
                        'propagated' => false,
                        'userId' => $userId,
                    ];
                }
            }

            DB::table(Table::CHANGEDFIELDS)->insert($data);
        }
    }
}
