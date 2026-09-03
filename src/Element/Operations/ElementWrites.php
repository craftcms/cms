<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Operations;

use CraftCms\Cms\Activity\ElementWriteActivity;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Events\ElementLifecyclePropagated;
use CraftCms\Cms\Element\Events\ElementPropagated;
use CraftCms\Cms\Element\Events\ElementPropagating;
use CraftCms\Cms\Element\Events\ElementResaved;
use CraftCms\Cms\Element\Events\ElementResaving;
use CraftCms\Cms\Element\Events\ElementSaved;
use CraftCms\Cms\Element\Events\ElementSaving;
use CraftCms\Cms\Element\Events\ElementSearchIndexUpdating;
use CraftCms\Cms\Element\Events\ElementsPropagated;
use CraftCms\Cms\Element\Events\ElementsPropagating;
use CraftCms\Cms\Element\Events\ElementsResaved;
use CraftCms\Cms\Element\Events\ElementsResaving;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Element\Exceptions\UnsupportedSiteException;
use CraftCms\Cms\Element\Models\Element as ElementModel;
use CraftCms\Cms\Element\Models\ElementSiteSettings;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\Exceptions\ElementNotFoundException;
use CraftCms\Cms\Element\Queries\Exceptions\QueryAbortedException;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Search\Search;
use CraftCms\Cms\Shared\Exceptions\OperationAbortedException;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Facades\BulkOps;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Url;
use Exception;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\normalizeValue;
use function CraftCms\Cms\renderObjectTemplate;
use function CraftCms\Cms\t;

/** @internal */
#[Singleton]
readonly class ElementWrites
{
    public function __construct(
        private Elements $elements,
        private ElementUris $elementUris,
        private ElementCaches $elementCaches,
        private Search $search,
        private Sites $sites,
        private ElementWriteActivity $activity,
    ) {}

    public function saveElement(
        ElementInterface $element,
        bool $runValidation = true,
        bool $propagate = true,
        ?bool $updateSearchIndex = null,
        bool $forceTouch = false,
        ?bool $crossSiteValidate = false,
        bool $saveContent = false,
    ): bool {
        $propagate = ! $element->id || $propagate;

        $duplicateOf = $element->duplicateOf;
        $element->duplicateOf = null;

        $isNewForSite = $element->isNewForSite;
        $element->isNewForSite = false;

        try {
            return $this->saveInternal(
                $element,
                $runValidation,
                $propagate,
                $updateSearchIndex,
                forceTouch: $forceTouch,
                crossSiteValidate: $crossSiteValidate ?? false,
                saveContent: $saveContent,
                recordActivity: $duplicateOf === null,
            );
        } finally {
            $element->duplicateOf = $duplicateOf;
            $element->isNewForSite = $isNewForSite;
        }
    }

    /**
     * @param  array<int, array{siteId:int, siteUid:string, propagate:bool, enabledByDefault:bool}>|null  $supportedSites
     */
    public function save(
        ElementInterface $element,
        bool $runValidation = true,
        bool $propagate = true,
        ?bool $updateSearchIndex = null,
        ?array $supportedSites = null,
        bool $forceTouch = false,
        bool $crossSiteValidate = false,
        bool $saveContent = false,
        ?ElementSiteSettings &$siteSettingsRecord = null,
    ): bool {
        return $this->saveInternal(
            $element,
            $runValidation,
            $propagate,
            $updateSearchIndex,
            $supportedSites,
            $forceTouch,
            $crossSiteValidate,
            $saveContent,
            $siteSettingsRecord,
            recordActivity: $element->duplicateOf === null,
        );
    }

    public function resaveElements(
        ElementQueryInterface $query,
        bool $continueOnError = false,
        bool $skipRevisions = true,
        ?bool $updateSearchIndex = null,
        bool $touch = false,
    ): void {
        event(new ElementsResaving($query));

        BulkOps::ensure(function () use ($query, $skipRevisions, $touch, $updateSearchIndex, $continueOnError) {
            $position = 0;

            try {
                $query->cursor()->each(function (ElementInterface $element) use ($continueOnError, $query, &$position, $skipRevisions, $touch, $updateSearchIndex) {
                    $position++;

                    $element->ruleset->useScenario(ElementRules::SCENARIO_ESSENTIALS);
                    $element->resaving = true;

                    $throwable = null;
                    try {
                        event(new ElementResaving($query, $element, $position));

                        if ($skipRevisions) {
                            $label = $element->getUiLabel();
                            $label = $label !== '' ? "$label ($element->id)" : sprintf('%s %s',
                                $element::lowerDisplayName(), $element->id);
                            try {
                                if (ElementHelper::isRevision($element)) {
                                    throw new InvalidElementException($element, "Skipped resaving $label because it's a revision.");
                                }
                            } catch (Throwable $rootException) {
                                throw new InvalidElementException($element, "Skipped resaving $label due to an error obtaining its root element: ".$rootException->getMessage());
                            }
                        }

                        $this->save(
                            element: $element,
                            updateSearchIndex: $updateSearchIndex,
                            forceTouch: $touch,
                            saveContent: true,
                        );
                    } catch (Throwable $throwable) {
                        if (! $continueOnError) {
                            throw $throwable;
                        }

                        report($throwable);
                    }

                    event(new ElementResaved($query, $element, $position, $throwable));
                });
            } catch (QueryAbortedException) {
                // Fail silently
            }
        });

        event(new ElementsResaved($query));
    }

    /**
     * @param  int[]|int|null  $siteIds
     */
    public function propagateElements(
        ElementQueryInterface $query,
        array|int|null $siteIds = null,
        bool $continueOnError = false,
    ): void {
        event(new ElementsPropagating($query));

        if ($siteIds !== null) {
            $siteIds = array_map(fn ($siteId) => $siteId, (array) $siteIds);
        }

        BulkOps::ensure(function () use ($query, $siteIds, $continueOnError) {
            $position = 0;

            try {
                $query->cursor()->each(function (ElementInterface $element) use ($continueOnError, $query, &$position, $siteIds) {
                    $position++;

                    event(new ElementPropagating($query, $element, $position));

                    $element->ruleset->useScenario(ElementRules::SCENARIO_ESSENTIALS);
                    $supportedSites = Arr::keyBy(ElementHelper::supportedSitesForElement($element), 'siteId');
                    $supportedSiteIds = array_keys($supportedSites);
                    $elementSiteIds = $siteIds !== null ? array_intersect($siteIds,
                        $supportedSiteIds) : $supportedSiteIds;
                    $elementType = $element::class;

                    $throwable = null;
                    try {
                        $element->newSiteIds = [];

                        foreach ($elementSiteIds as $siteId) {
                            if ($siteId === $element->siteId) {
                                continue;
                            }

                            $siteElement = $this->elements->getElementById($element->id, $elementType, $siteId);
                            if ($siteElement === null || $siteElement->dateUpdated < $element->dateUpdated) {
                                $siteElement ??= false;
                                $this->propagate($element, $supportedSites, $siteId, $siteElement);
                            }
                        }

                        $element->markAsDirty();
                        $element->afterPropagate(false);
                    } catch (Throwable $throwable) {
                        if (! $continueOnError) {
                            throw $throwable;
                        }

                        report($throwable);
                    }

                    event(new ElementPropagated($query, $element, $position, $throwable));

                    BulkOps::trackElement($element);
                    $this->elementCaches->invalidateForElement($element);
                });
            } catch (QueryAbortedException) {
                // Fail silently
            }
        });

        event(new ElementsPropagated($query));
    }

    public function propagateElement(
        ElementInterface $element,
        int $siteId,
        ElementInterface|false|null $siteElement = null,
    ): ElementInterface {
        $supportedSites = Arr::keyBy(ElementHelper::supportedSitesForElement($element), 'siteId');

        BulkOps::ensure(function () use ($element, $supportedSites, $siteId, &$siteElement) {
            $this->propagate($element, $supportedSites, $siteId, $siteElement);
            BulkOps::trackElement($element);
        });

        $this->elementCaches->invalidateForElement($element);

        return $siteElement;
    }

    /**
     * @param  array<int, array{siteId:int, siteUid:string, propagate:bool, enabledByDefault:bool}>  $supportedSites
     */
    public function propagate(
        ElementInterface $element,
        array $supportedSites,
        int $siteId,
        ElementInterface|false|null &$siteElement = null,
        bool $crossSiteValidate = false,
        bool $saveContent = true,
        ?ElementSiteSettings &$siteSettingsRecord = null,
    ): bool {
        return $this->propagateInternal(
            $element,
            $supportedSites,
            $siteId,
            $siteElement,
            $crossSiteValidate,
            $saveContent,
            $siteSettingsRecord,
        );
    }

    /**
     * @throws ElementNotFoundException
     * @throws UnsupportedSiteException
     * @throws Throwable
     */
    /**
     * @param  array<int, array{siteId:int, siteUid:string, propagate:bool, enabledByDefault:bool}>|null  $supportedSites
     */
    protected function saveInternal(
        ElementInterface $element,
        bool $runValidation = true,
        bool $propagate = true,
        ?bool $updateSearchIndex = null,
        ?array $supportedSites = null,
        bool $forceTouch = false,
        bool $crossSiteValidate = false,
        bool $saveContent = false,
        ?ElementSiteSettings &$siteSettingsRecord = null,
        ?bool $inheritedUpdateSearchIndex = null,
        bool $recordActivity = true,
    ): bool {
        $originalScenario = $element->ruleset->getScenario();
        try {
            $isNewElement = ! $element->id;
            $trackChanges = ElementHelper::shouldTrackChanges($element);
            $activityState = $this->activity->capture($element, $recordActivity, $isNewElement);

            $propagate = $propagate && $element::isLocalized() && $this->sites->isMultiSite();
            $originalPropagateAll = $element->propagateAll;
            $originalFirstSave = $element->firstSave;
            $originalIsNewForSite = $element->isNewForSite;
            $originalDateUpdated = $element->dateUpdated;
            $dirtyAttributes = [];

            $element->firstSave = (
                ! $element->getIsDraft() &&
                ! $element->getIsRevision() &&
                ($element->firstSave || $isNewElement)
            );

            if ($isNewElement) {
                $element->uid ??= Str::uuid()->toString();

                if (! $element->getIsDraft() && ! $element->getIsRevision()) {
                    $element->propagateAll = true;
                }
            }

            event($event = new ElementSaving($element, $isNewElement));

            if (! $event->isValid || ! $element->beforeSave($isNewElement)) {
                $this->resetElement($element, $originalFirstSave, $originalIsNewForSite, $originalPropagateAll);

                return false;
            }

            $supportedSites ??= Arr::keyBy(ElementHelper::supportedSitesForElement($element), 'siteId');

            if (! isset($supportedSites[$element->siteId])) {
                $this->resetElement($element, $originalFirstSave, $originalIsNewForSite, $originalPropagateAll);

                throw new UnsupportedSiteException($element, $element->siteId,
                    'Attempting to save an element in an unsupported site.');
            }

            if (count($supportedSites) === 1 && ! $element->getEnabledForSite()) {
                $element->enabled = false;
                $element->setEnabledForSite(true);
            }

            if (! $runValidation && $element::hasTitles()) {
                $element->validate('title');

                if ($element->errors()->has('title')) {
                    $element->title = $isNewElement
                        ? t('New {type}', ['type' => $element::displayName()])
                        : $element::displayName().' '.$element->id;
                }
            }

            $fieldLayout = $element->getFieldLayout();
            $dirtyFields = $element->getDirtyFields();

            if (! $isNewElement && ! $element->isNewForSite) {
                $siteSettingsRecord = ElementSiteSettings::query()
                    ->where('elementId', $element->id)
                    ->where('siteId', $element->siteId)
                    ->first();
            }

            $element->isNewForSite = $siteSettingsRecord === null;

            if ($runValidation) {
                if ($element->propagating && ! (
                    $element->getIsDerivative() &&
                    $element->getIsDraft() &&
                    $element->getEnabledForSite() &&
                    ! $element->getCanonical()->getEnabledForSite()
                )) {
                    $names = array_map(
                        fn (string $handle) => "field:$handle",
                        array_unique(array_merge($dirtyFields, $element->getModifiedFields())),
                    );
                } else {
                    $names = null;
                }

                if (($names === null || ! empty($names)) && ! $element->validate($names)) {
                    Log::info('Element not saved due to validation error: '.print_r($element->errors()->all(), true), [__METHOD__]);
                    $this->resetElement($element, $originalFirstSave, $originalIsNewForSite, $originalPropagateAll);

                    return false;
                }
            }

            $success = BulkOps::ensure(function () use (
                $element,
                $isNewElement,
                $forceTouch,
                $saveContent,
                $updateSearchIndex,
                $fieldLayout,
                $propagate,
                $supportedSites,
                $crossSiteValidate,
                $runValidation,
                $dirtyFields,
                $trackChanges,
                $originalFirstSave,
                $originalIsNewForSite,
                $originalPropagateAll,
                $originalDateUpdated,
                $inheritedUpdateSearchIndex,
                $activityState,
                &$dirtyAttributes,
                &$siteSettingsRecord,
            ) {
                $resolvedUpdateSearchIndex = $updateSearchIndex ?? $inheritedUpdateSearchIndex ?? true;
                $newSiteIds = $element->newSiteIds;
                $element->newSiteIds = [];

                DB::beginTransaction();

                try {
                    $this->updateModel($element, $isNewElement, $forceTouch, $fieldLayout, $trackChanges, $dirtyAttributes);

                    if ($siteSettingsRecord === null) {
                        $siteSettingsRecord = new ElementSiteSettings;
                        $siteSettingsRecord->elementId = $element->id;
                        $siteSettingsRecord->siteId = $element->siteId;
                    }

                    $title = $element::hasTitles() ? $element->title : null;
                    $siteSettingsRecord->title = $title !== null && $title !== '' ? $title : null;
                    $siteSettingsRecord->slug = $element->slug;
                    $siteSettingsRecord->uri = $element->uri;

                    $enabledForSite = $element->getEnabledForSite();
                    if (! $siteSettingsRecord->exists || $siteSettingsRecord->enabled !== $enabledForSite) {
                        $siteSettingsRecord->enabled = $enabledForSite;
                    }

                    if ($trackChanges && ! $element->isNewForSite) {
                        array_push($dirtyAttributes, ...array_keys(Arr::only($siteSettingsRecord->getDirty(), [
                            'slug',
                            'uri',
                        ])));
                        if ($siteSettingsRecord->isDirty('enabled')) {
                            $dirtyAttributes[] = 'enabledForSite';
                        }
                    }

                    $saveContent = $saveContent || $element->isNewForSite;
                    $generatedFields = $fieldLayout?->getGeneratedFields() ?? [];

                    if ($saveContent || ! empty($dirtyFields) || ! empty($generatedFields)) {
                        $oldContent = $siteSettingsRecord->content ?? [];
                        if (is_string($oldContent)) {
                            $oldContent = $oldContent !== '' ? Json::decode($oldContent) : [];
                        }

                        $content = [];
                        $validUids = [];

                        if ($fieldLayout) {
                            foreach ($fieldLayout->getCustomFields() as $field) {
                                $validUids[$field->layoutElement->uid] = true;

                                if (($saveContent || in_array($field->handle, $dirtyFields)) && $field::dbType() !== null) {
                                    $value = $element->getFieldValue($field->handle);
                                    if ($element->isNewForSite && $field->isValueEmpty($value, $element)) {
                                        continue;
                                    }
                                    $serializedValue = $field->serializeValueForDb($value, $element);
                                    if ($serializedValue !== null) {
                                        $content[$field->layoutElement->uid] = $serializedValue;
                                    } elseif (! $saveContent) {
                                        unset($oldContent[$field->layoutElement->uid]);
                                    }
                                }
                            }

                            if ($oldContent) {
                                foreach ($generatedFields as $field) {
                                    if (isset($oldContent[$field['uid']])) {
                                        $content[$field['uid']] = $oldContent[$field['uid']];
                                    }
                                }
                            }
                        }

                        if (! $saveContent && $oldContent) {
                            foreach ($oldContent as $uid => $value) {
                                if (! isset($content[$uid]) && isset($validUids[$uid])) {
                                    $content[$uid] = $value;
                                }
                            }
                        }

                        $siteSettingsRecord->content = $content ?: null;
                    }

                    if (! $siteSettingsRecord->save()) {
                        $this->resetElement($element, $originalFirstSave, $originalIsNewForSite, $originalPropagateAll);

                        throw new Exception('Couldn’t save elements’ site settings record.');
                    }

                    $element->siteSettingsId = $siteSettingsRecord->id;

                    if ($trackChanges) {
                        array_push($dirtyAttributes, ...$element->getDirtyAttributes());
                        $element->setDirtyAttributes($dirtyAttributes, false);
                    }

                    $element->afterSave($isNewElement);

                    $dirtyAttributes = $element->getDirtyAttributes();

                    $siteElements = [];
                    $siteSettingsRecords = [];

                    if ($propagate) {
                        $otherSiteIds = array_keys(Arr::except($supportedSites, $element->siteId));

                        if (! empty($otherSiteIds)) {
                            if (! $isNewElement) {
                                $siteElements = $element->getLocalizedQuery()
                                    ->siteId($otherSiteIds)
                                    ->status(null)
                                    ->indexBy('siteId')
                                    ->all();
                            }

                            foreach (array_keys($supportedSites) as $siteId) {
                                if ($siteId === $element->siteId) {
                                    continue;
                                }

                                $siteElement = $siteElements[$siteId] ?? false;
                                $siteElementRecord = null;
                                if (! $this->propagateInternal(
                                    $element,
                                    $supportedSites,
                                    $siteId,
                                    $siteElement,
                                    crossSiteValidate: $runValidation && $crossSiteValidate,
                                    siteSettingsRecord: $siteElementRecord,
                                    inheritedUpdateSearchIndex: $resolvedUpdateSearchIndex,
                                )) {
                                    throw new InvalidArgumentException;
                                }

                                $siteElements[$siteId] = $siteElement;
                                $siteSettingsRecords[$siteId] = $siteElementRecord;
                            }
                        }
                    }

                    if (! $element->propagating && ! empty($generatedFields)) {
                        $siteElements[$element->siteId] = $element;
                        $siteSettingsRecords[$element->siteId] = $siteSettingsRecord;

                        Event::listen(function (ElementLifecyclePropagated $event) use ($element, $generatedFields, $siteElements, $siteSettingsRecords) {
                            if ($event->element->id !== $element->id) {
                                return;
                            }

                            foreach ($siteElements as $siteId => $siteElement) {
                                $siteSettingsRecord = $siteSettingsRecords[$siteId];
                                $content = $siteSettingsRecord->content ?? [];
                                if (is_string($content)) {
                                    $content = $content !== '' ? Json::decode($content) : [];
                                }
                                $generatedFieldValues = [];
                                $updated = false;

                                foreach ($generatedFields as $field) {
                                    $value = renderObjectTemplate($field['template'] ?? '', $siteElement, escaperStrategy: 'html');
                                    $value = normalizeValue($value) ?? '';

                                    if ($value !== ($content[$field['uid']] ?? '')) {
                                        $updated = true;
                                    }
                                    if ($value !== '') {
                                        $content[$field['uid']] = $value;
                                        if (($field['handle'] ?? '') !== '') {
                                            $generatedFieldValues[$field['handle']] = $value;
                                        }
                                    } else {
                                        unset($content[$field['uid']]);
                                    }
                                }

                                if ($updated) {
                                    $siteSettingsRecord->content = $content;
                                    $siteSettingsRecord->save();
                                    $siteElement->setGeneratedFieldValues($generatedFieldValues);
                                }
                            }
                        });
                    }

                    if (
                        ! $element->propagating &&
                        ! $element->duplicateOf &&
                        ! $element->mergingCanonicalChanges
                    ) {
                        $element->afterPropagate($isNewElement);
                        BulkOps::trackElement($element);
                    }

                    $this->activity->record(
                        $activityState,
                        $element,
                        $isNewElement,
                        $dirtyAttributes,
                        $dirtyFields,
                        $siteElements,
                    );
                    DB::commit();
                } catch (Throwable $throwable) {
                    DB::rollBack();

                    $this->resetElement($element, $originalFirstSave, $originalIsNewForSite, $originalPropagateAll);
                    $element->dateUpdated = $originalDateUpdated;

                    if ($throwable instanceof InvalidArgumentException) {
                        return false;
                    }

                    throw $throwable;
                } finally {
                    $element->newSiteIds = $newSiteIds;
                }

                if (! $element->propagating) {
                    if (! $isNewElement) {
                        $deleteCondition = fn (Builder $query) => $query
                            ->where('elementId', $element->id)
                            ->whereNotIn('siteId', array_keys($supportedSites));

                        DB::table(Table::ELEMENTS_SITES)->where($deleteCondition)->delete();
                        DB::table(Table::SEARCHINDEX)->where($deleteCondition)->delete();
                        DB::table(Table::SEARCHINDEXQUEUE)->where($deleteCondition)->delete();
                    }

                    $this->elementCaches->invalidateForElement($element);
                }

                if ($resolvedUpdateSearchIndex && ! $element->getIsRevision() && ! ElementHelper::isRevision($element)) {
                    $searchableDirtyFields = array_filter(
                        $dirtyFields,
                        fn (string $handle) => $fieldLayout?->getFieldByHandle($handle)?->searchable,
                    );

                    if (
                        ! $trackChanges ||
                        ! empty($searchableDirtyFields) ||
                        ! empty(array_intersect($dirtyAttributes, ElementHelper::searchableAttributes($element)))
                    ) {
                        event($event = new ElementSearchIndexUpdating($element));

                        if ($event->isValid) {
                            $this->updateElementSearchIndex($element, $searchableDirtyFields, $propagate);
                        }
                    }
                }

                if ($trackChanges) {
                    $userId = currentUser()?->getCraftUserId();
                    $timestamp = now();

                    foreach ($dirtyAttributes as $attributeName) {
                        DB::table(Table::CHANGEDATTRIBUTES)
                            ->upsert([
                                'elementId' => $element->id,
                                'siteId' => $element->siteId,
                                'attribute' => $attributeName,
                                'dateUpdated' => $timestamp,
                                'propagated' => $element->propagating,
                                'userId' => $userId,
                            ], ['elementId', 'siteId', 'attribute']);
                    }

                    if ($fieldLayout) {
                        foreach ($dirtyFields as $fieldHandle) {
                            if (($field = $fieldLayout->getFieldByHandle($fieldHandle)) !== null) {
                                DB::table(Table::CHANGEDFIELDS)
                                    ->upsert([
                                        'elementId' => $element->id,
                                        'siteId' => $element->siteId,
                                        'fieldId' => $field->id,
                                        'layoutElementUid' => $field->layoutElement->uid,
                                        'dateUpdated' => $timestamp,
                                        'propagated' => $element->propagating,
                                        'userId' => $userId,
                                    ], ['elementId', 'siteId', 'fieldId', 'layoutElementUid']);
                            }
                        }
                    }
                }

                return true;
            });

            if (! $success) {
                return false;
            }
        } finally {
            $element->ruleset->useScenario($originalScenario);
        }

        event(new ElementSaved($element, $isNewElement));

        $element->markAsClean();
        $this->resetElement($element, $originalFirstSave, $originalIsNewForSite, $originalPropagateAll);

        return true;
    }

    /**
     * @throws UnsupportedSiteException
     */
    /**
     * @param  array<int, array{siteId:int, siteUid:string, propagate:bool, enabledByDefault:bool}>  $supportedSites
     */
    protected function propagateInternal(
        ElementInterface $element,
        array $supportedSites,
        int $siteId,
        ElementInterface|false|null &$siteElement = null,
        bool $crossSiteValidate = false,
        bool $saveContent = true,
        ?ElementSiteSettings &$siteSettingsRecord = null,
        ?bool $inheritedUpdateSearchIndex = null,
    ): bool {
        if (! isset($supportedSites[$siteId])) {
            throw new UnsupportedSiteException($element, $siteId, 'Attempting to propagate an element to an unsupported site.');
        }

        $siteInfo = $supportedSites[$siteId];

        if ($siteElement === null && $element->id) {
            $siteElement = $this->elements->getElementById($element->id, $element::class, $siteInfo['siteId']);
        } elseif (! $siteElement) {
            $siteElement = null;
        }

        if ($siteElement === null) {
            $siteElement = clone $element;
            $siteElement->siteId = $siteInfo['siteId'];
            $siteElement->siteSettingsId = null;
            $siteElement->setEnabledForSite($siteInfo['enabledByDefault']);
            $siteElement->isNewForSite = ! $siteElement->duplicateOf?->getIsRevision();
            $element->newSiteIds[] = $siteInfo['siteId'];
        } elseif ($element->propagateAll) {
            $oldSiteElement = $siteElement;
            $siteElement = clone $element;
            $siteElement->siteId = $oldSiteElement->siteId;
            $siteElement->setEnabledForSite($oldSiteElement->getEnabledForSite());
            $siteElement->uri = $oldSiteElement->uri;
        } else {
            $siteElement->enabled = $element->enabled;
            $siteElement->resaving = $element->resaving;
        }

        $enabledForSite = $element->getEnabledForSite($siteElement->siteId);
        if ($enabledForSite !== null) {
            $siteElement->setEnabledForSite($enabledForSite);
        }

        $siteElement->dateCreated = $element->dateCreated;
        $siteElement->dateUpdated = $element->dateUpdated;

        if (
            $element::hasTitles() &&
            (
                $siteElement->getTitleTranslationKey() === $element->getTitleTranslationKey() ||
                ($element->propagateRequired && empty($siteElement->title))
            )
        ) {
            $siteElement->title = $element->title;
        }

        if (
            $element->slug !== null &&
            (
                $siteElement->getSlugTranslationKey() === $element->getSlugTranslationKey() ||
                ($element->propagateRequired && empty($siteElement->slug))
            )
        ) {
            $siteElement->slug = $element->slug;
        }

        if (
            $element::hasUris() &&
            (
                $siteElement->isNewForSite ||
                in_array('uri', $element->getDirtyAttributes()) ||
                $element->resaving ||
                ElementHelper::containsTempSlug($siteElement->uri)
            )
        ) {
            try {
                $this->elementUris->setElementUri($siteElement);
            } catch (OperationAbortedException) {
                // carry on
            }
        }

        $siteElement->ruleset->useScenario(ElementRules::SCENARIO_ESSENTIALS);

        if (
            ($crossSiteValidate || $element->propagateRequired) &&
            $siteElement->enabled &&
            $siteElement->getEnabledForSite()
        ) {
            $siteElement->ruleset->useScenario(ElementRules::SCENARIO_LIVE);
        }

        $siteElement->setDirtyAttributes(array_filter($element->getDirtyAttributes(),
            fn (string $attribute): bool => $attribute !== 'title' && $attribute !== 'slug'));

        if ($saveContent) {
            if ($siteElement->isNewForSite) {
                $siteElement->setFieldValues($element->getFieldValues());
            } else {
                $fieldLayout = $element->getFieldLayout();

                if ($fieldLayout !== null) {
                    foreach ($fieldLayout->getCustomFields() as $field) {
                        if (
                            $element->propagateAll ||
                            (
                                $element->propagateRequired &&
                                $field->layoutElement->required &&
                                $field->isValueEmpty($siteElement->getFieldValue($field->handle), $siteElement)
                            ) ||
                            (
                                $element->isFieldDirty($field->handle) &&
                                $field->getTranslationKey($siteElement) === $field->getTranslationKey($element)
                            )
                        ) {
                            $field->propagateValue($element, $siteElement);
                        }
                    }
                }
            }
        }

        $siteElement->propagating = true;
        $siteElement->propagatingFrom = $element;

        $success = $this->saveInternal(
            $siteElement,
            $crossSiteValidate,
            false,
            null,
            $supportedSites,
            false,
            false,
            $saveContent,
            $siteSettingsRecord,
            $inheritedUpdateSearchIndex,
        );

        if ($success) {
            return true;
        }

        if ($siteElement->errors()->isNotEmpty()) {
            return $this->crossSiteValidationErrors($siteElement, $element);
        }

        $error = 'Couldn’t propagate element to other site due to validation errors:';

        foreach ($siteElement->errors()->all() as $attributeError) {
            $error .= "\n- ".$attributeError;
        }

        Log::error($error);

        throw new Exception('Couldn’t propagate element to other site.');
    }

    private function crossSiteValidationErrors(
        ElementInterface $siteElement,
        ElementInterface $element,
    ): bool {
        $propagateToSite = $this->sites->getSiteById($siteElement->siteId);

        $user = currentUser()?->asElement();
        $message = t('Validation errors for site: “{siteName}“', [
            'siteName' => $propagateToSite?->getName(),
        ]);

        if ($user &&
            $this->sites->isMultiSite() &&
            $user->can("editSite:{$propagateToSite?->uid}") &&
            $siteElement->canSave($user)
        ) {
            $queryParams = Arr::except(request()->query(), 'site');
            $url = Url::url($siteElement->getCpEditUrl(), $queryParams + ['prevalidate' => 1]);
            $message = Html::beginTag('a', [
                'href' => $url,
                'class' => 'cross-site-validate',
                'target' => '_blank',
            ]).
            $message.
            Html::tag('span', '', [
                'data-icon' => 'external',
                'aria-label' => t('Open in a new tab'),
                'role' => 'img',
            ]).
            Html::endTag('a');
        }

        $element->errors()->add('global', $message);

        return false;
    }

    /**
     * @param  array<int, int|string>  $dirtyAttributes
     */
    private function updateModel(
        ElementInterface $element,
        bool $isNewElement,
        bool $forceTouch,
        ?FieldLayout $fieldLayout,
        bool $trackChanges,
        array &$dirtyAttributes,
    ): void {
        if ($element->propagating) {
            return;
        }

        if (! $isNewElement) {
            $elementModel = ElementModel::withTrashed()->find($element->id);

            if (! $elementModel) {
                throw new ElementNotFoundException("No element exists with the ID '$element->id'");
            }
        } else {
            $elementModel = new ElementModel;
            $elementModel->type = $element::class;
        }

        $elementModel->uid = $element->uid;
        $canonicalId = $element->getCanonicalId();
        $elementModel->canonicalId = $canonicalId !== $element->id ? $canonicalId : null;
        $elementModel->draftId = (int) $element->draftId ?: null;
        $elementModel->revisionId = (int) $element->revisionId ?: null;
        $elementModel->fieldLayoutId = $element->fieldLayoutId = $element->fieldLayoutId ?? $fieldLayout->id ?? 0 ?: null;
        $elementModel->enabled = (bool) $element->enabled;
        $elementModel->archived = (bool) $element->archived;
        $elementModel->dateLastMerged = Query::prepareDateForDb($element->dateLastMerged);
        $elementModel->dateDeleted = Query::prepareDateForDb($element->dateDeleted);

        $now = Query::prepareDateForDb(now());

        // Element timestamps are stored in UTC (see Query::prepareDateForDb(), and the naked
        // `dateTime` columns the install migration creates), but Eloquent formats and re-parses
        // date attributes in PHP’s default timezone — which Cms::setDefaultTimezone() sets to the
        // system timezone. Letting its automatic timestamps manage these columns would write local
        // time into a UTC column, so set them ourselves.
        $elementModel->timestamps = false;

        if ($isNewElement) {
            $elementModel->dateCreated = isset($element->dateCreated)
                ? Query::prepareDateForDb($element->dateCreated)
                : $now;
            $elementModel->dateUpdated = isset($element->dateUpdated)
                ? Query::prepareDateForDb($element->dateUpdated)
                : $now;
        } elseif (! $element->resaving || $forceTouch) {
            $elementModel->dateUpdated = $now;
        }

        if ($trackChanges) {
            array_push($dirtyAttributes, ...array_keys(Arr::only($elementModel->getDirty(), [
                'fieldLayoutId',
                'enabled',
                'archived',
            ])));
        }

        $elementModel->save();

        // Read the timestamps back as they were stored, so DateTimeHelper::toDateTime() applies its
        // UTC assumption to the raw string. Going through the model’s date attributes would instead
        // hand it a Carbon that Eloquent already re-parsed in the system timezone, which is what
        // made a freshly saved element report a different instant than the same element loaded back
        // out of the database.
        $dateCreated = DateTimeHelper::toDateTime($elementModel->getRawOriginal('dateCreated'));

        if ($dateCreated === false) {
            throw new Exception('There was a problem calculating dateCreated.');
        }

        $dateUpdated = DateTimeHelper::toDateTime($elementModel->getRawOriginal('dateUpdated'));

        if ($dateUpdated === false) {
            throw new Exception('There was a problem calculating dateUpdated.');
        }

        $element->dateCreated = $dateCreated;
        $element->dateUpdated = $dateUpdated;

        if ($isNewElement) {
            $element->id = $elementModel->id;

            if ($element->tempId && $element->uri) {
                $element->uri = str_replace($element->tempId, (string) $element->id, $element->uri);
                $element->tempId = null;
            }

            $element->afterAssignedId();
        }
    }

    /**
     * @param  string[]  $searchableDirtyFields
     */
    private function updateElementSearchIndex(
        ElementInterface $element,
        array $searchableDirtyFields,
        bool $propagate,
        ?bool $updateForOwner = null,
    ): void {
        if ($element->updateSearchIndexImmediately ?? app()->runningInConsole()) {
            $this->search->indexElementAttributes($element, $searchableDirtyFields);
        } else {
            $this->search->queueIndexElement($element, $searchableDirtyFields);
        }

        $updateForOwner = (
            $element instanceof NestedElementInterface &&
            ($field = $element->getField()) &&
            $field->searchable &&
            ($updateForOwner ??
                $element->getIsCanonical() &&
                isset($element->fieldId) &&
                isset($element->updateSearchIndexForOwner) &&
                $element->updateSearchIndexForOwner
            )
        );

        if ($updateForOwner) {
            /** @var NestedElementInterface $element */
            $owner = $element->getOwner();
            if ($owner) {
                $this->updateElementSearchIndex($owner, [$field->handle], $propagate, true);
                $this->elementCaches->invalidateForElement($owner);
            }
        }
    }

    private function resetElement(
        ElementInterface $element,
        bool $originalFirstSave,
        bool $originalIsNewForSite,
        bool $originalPropagateAll,
    ): void {
        $element->firstSave = $originalFirstSave;
        $element->isNewForSite = $originalIsNewForSite;
        $element->propagateAll = $originalPropagateAll;
    }
}
