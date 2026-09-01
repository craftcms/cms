<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Operations;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Element\Exceptions\UnsupportedSiteException;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Shared\Exceptions\OperationAbortedException;
use CraftCms\Cms\Structure\Enums\Mode;
use CraftCms\Cms\Structure\Structures;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\BulkOps;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Typecast;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;
use UnitEnum;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;

/** @internal */
readonly class ElementDuplicates
{
    public function __construct(
        private ElementWrites $elementWrites,
        private ElementUris $elementUris,
        private ElementDeletions $elementDeletions,
        private Drafts $drafts,
        private Structures $structures,
    ) {}

    /**
     * @param  array<string, mixed>  $newAttributes
     *
     * @throws UnsupportedSiteException
     * @throws InvalidElementException
     * @throws HttpException
     * @throws Throwable
     */
    public function duplicateElement(
        ElementInterface $element,
        array $newAttributes = [],
        bool $placeInStructure = true,
        bool $asUnpublishedDraft = false,
        bool $checkAuthorization = false,
        bool $copyModifiedFields = false,
    ): ElementInterface {
        if (! $element->id) {
            throw new Exception('Attempting to duplicate an unsaved element.');
        }

        $element->getFieldValues();

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

        $siteAttributes = Arr::pull($newAttributes, 'siteAttributes', []);

        Typecast::configure($mainClone, Arr::merge(
            $newAttributes,
            $siteAttributes[$mainClone->siteId] ?? [],
        ));

        $supportedSites = Arr::keyBy(ElementHelper::supportedSitesForElement($mainClone), 'siteId');
        if (! isset($supportedSites[$mainClone->siteId])) {
            throw new UnsupportedSiteException($element, $mainClone->siteId,
                'Attempting to duplicate an element in an unsupported site.');
        }

        $dirtyFields = $mainClone->getDirtyFields();
        foreach ($mainClone->getFieldValues() as $handle => $value) {
            if (is_object($value) && ! $value instanceof UnitEnum) {
                $mainClone->setFieldValue($handle, clone $value);
            }
        }
        $mainClone->setDirtyFields($dirtyFields, false);

        if ($checkAuthorization && ! (Gate::check('duplicate', $mainClone) && Gate::check('save', $mainClone))) {
            abort(403, 'User not authorized to duplicate this element.');
        }

        if ($mainClone->draftId && $mainClone->draftId === $element->draftId) {
            if ($element->getIsDerivative()) {
                $mainClone->draftName = $this->drafts->generateDraftName($element->getCanonicalId());
            } else {
                $mainClone->draftName = t('First draft');
            }
            $mainClone->draftNotes = null;
            $mainClone->setCanonicalId($element->getCanonicalId());
            $mainClone->draftId = $this->drafts->insertDraftRow(
                name: $mainClone->draftName,
                creatorId: currentUser()?->getCraftUserId(),
                canonicalId: $element->getCanonicalId(),
                trackChanges: $mainClone->trackDraftChanges,
            );
        }

        if ($asUnpublishedDraft) {
            $mainClone->draftName = t('First draft');
            $mainClone->draftNotes = null;
            $mainClone->setCanonicalId(null);
            $mainClone->draftId = $this->drafts->insertDraftRow(
                name: $mainClone->draftName,
                creatorId: currentUser()?->getCraftUserId(),
                trackChanges: $mainClone->trackDraftChanges,
            );
        }

        $mainClone->ruleset->useScenario(ElementRules::SCENARIO_ESSENTIALS);
        $mainClone->validate();

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
                if (! $this->elementWrites->save(
                    $mainClone,
                    false,
                    false,
                    null,
                    $supportedSites,
                    saveContent: true,
                )) {
                    throw new InvalidElementException($mainClone,
                        'Element '.$element->id.' could not be duplicated for site '.$element->siteId);
                }

                if ($copyModifiedFields) {
                    $this->copyModifiedFields($element, $mainClone);
                }

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

                $otherSiteIds = array_keys(Arr::except($supportedSites, $mainClone->siteId));
                if ($element->id && ! empty($otherSiteIds)) {
                    $siteElements = $element->getLocalizedQuery()
                        ->siteId($otherSiteIds)
                        ->status(null)
                        ->all();

                    foreach ($siteElements as $siteElement) {
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

                        Typecast::configure($siteClone, Arr::merge(
                            $newAttributes,
                            $siteAttributes[$siteElement->siteId] ?? [],
                        ));
                        $siteClone->siteId = $siteElement->siteId;

                        $dirtyFields = $siteClone->getDirtyFields();
                        foreach ($siteClone->getFieldValues() as $handle => $value) {
                            if (is_object($value) && ! $value instanceof UnitEnum) {
                                $siteClone->setFieldValue($handle, clone $value);
                            }
                        }
                        $siteClone->setDirtyFields($dirtyFields, false);

                        if ($element::hasUris()) {
                            $siteClone->validate(['slug']);

                            if ($siteClone->errors()->has('slug')) {
                                throw new InvalidElementException($siteClone,
                                    "Element $element->id could not be duplicated for site $siteElement->siteId: ".$siteClone->errors()->first('slug'));
                            }

                            try {
                                $this->elementUris->setElementUri($siteClone);
                            } catch (OperationAbortedException) {
                                // Oh well, not worth bailing over
                            }
                        }

                        if (! $this->elementWrites->save(
                            $siteClone,
                            false,
                            false,
                            supportedSites: $supportedSites,
                            saveContent: true,
                        )) {
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

                    foreach ($supportedSites as $siteId => $siteInfo) {
                        if (! isset($propagatedTo[$siteId]) && $siteInfo['propagate']) {
                            $siteClone = $element->getIsDerivative() ? null : false;
                            if (! $this->elementWrites->propagate($mainClone, $supportedSites, $siteId, $siteClone)) {
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

                $mainClone->afterPropagate(empty($newAttributes['id']));

                DB::commit();
            } catch (Throwable $throwable) {
                DB::rollBack();
                throw $throwable;
            }

            $mainClone->duplicateOf = null;

            if ($asUnpublishedDraft && $element->isProvisionalDraft) {
                $this->elementDeletions->deleteElementById($element->id);
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
            $modifiedAttributes = [
                ...$modifiedAttributes,
                ...$from->duplicateOf->getModifiedAttributes(),
                ...$from->duplicateOf->getDirtyAttributes(),
            ];
            $modifiedFields = [
                ...$modifiedFields,
                ...$from->duplicateOf->getModifiedFields(),
                ...$from->duplicateOf->getDirtyFields(),
            ];
        }

        $modifiedAttributes = array_unique($modifiedAttributes);
        $modifiedFields = array_unique($modifiedFields);

        $userId = currentUser()?->getCraftUserId();

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
