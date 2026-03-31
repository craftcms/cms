<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Actions;

use craft\base\ElementInterface;
use craft\base\NestedElementInterface;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Events\AfterPropagate;
use CraftCms\Cms\Element\Events\AfterSaveElement;
use CraftCms\Cms\Element\Events\BeforeSaveElement;
use CraftCms\Cms\Element\Events\BeforeUpdateSearchIndex;
use CraftCms\Cms\Element\Exceptions\UnsupportedSiteException;
use CraftCms\Cms\Element\Models\Element as ElementModel;
use CraftCms\Cms\Element\Models\ElementSiteSettings;
use CraftCms\Cms\Element\Queries\Exceptions\ElementNotFoundException;
use CraftCms\Cms\Search\Search;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Facades\BulkOps;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\Support\Str;
use DateTime;
use Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

use function CraftCms\Cms\normalizeValue;
use function CraftCms\Cms\renderObjectTemplate;
use function CraftCms\Cms\t;

/** @internal */
class SaveElementAction
{
    /**
     * @var bool|null Whether we should be updating search indexes for elements if not told explicitly.
     */
    private ?bool $updateSearchIndex = null;

    private bool $trackChanges;

    private bool $originalPropagateAll;

    private bool $originalFirstSave;

    private bool $originalIsNewForSite;

    private ?DateTime $originalDateUpdated = null;

    private array $dirtyAttributes = [];

    public function __construct(
        private readonly ElementCaches $elementCaches,
        private readonly Search $search,
        private readonly Sites $sites,
    ) {}

    /**
     * Saves an element.
     *
     * @param  ElementInterface  $element  The element that is being saved
     * @param  bool  $runValidation  Whether the element should be validated
     * @param  bool  $propagate  Whether the element should be saved across all of its supported sites
     * @param  bool|null  $updateSearchIndex  Whether to update the element search index for the element
     *                                        (this will happen via a background job if this is a web request)
     * @param  array|null  $supportedSites  The element’s supported site info, indexed by site ID
     * @param  bool  $forceTouch  Whether to force the `dateUpdated` timestamp to be updated for the element,
     *                            regardless of whether it’s being resaved
     * @param  bool  $crossSiteValidate  Whether the element should be validated across all supported sites
     * @param  bool  $saveContent  Whether all the element’s content should be saved. When false (default) only dirty fields will be saved.
     *
     * @throws ElementNotFoundException if $element has an invalid $id
     * @throws UnsupportedSiteException if the element is being saved for a site it doesn’t support
     * @throws Throwable if reasons
     */
    public function handle(
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
        $isNewElement = ! $element->id;

        // Are we tracking changes?
        $this->trackChanges = ElementHelper::shouldTrackChanges($element);

        // Force propagation for new elements
        $propagate = $propagate && $element::isLocalized() && $this->sites->isMultiSite();
        $this->originalPropagateAll = $element->propagateAll;
        $this->originalFirstSave = $element->firstSave;
        $this->originalIsNewForSite = $element->isNewForSite;
        $this->originalDateUpdated = $element->dateUpdated;

        $element->firstSave = (
            ! $element->getIsDraft() &&
            ! $element->getIsRevision() &&
            ($element->firstSave || $isNewElement)
        );

        if ($isNewElement) {
            // Give it a UID right away
            $element->uid ??= Str::uuid()->toString();

            if (! $element->getIsDraft() && ! $element->getIsRevision()) {
                // Let Matrix fields, etc., know they should be duplicating their values across all sites.
                $element->propagateAll = true;
            }
        }

        event($event = new BeforeSaveElement($element, $isNewElement));

        if (! $event->isValid || ! $element->beforeSave($isNewElement)) {
            $this->resetElement($element);

            return false;
        }

        // Get the sites supported by this element
        $supportedSites ??= Arr::keyBy(ElementHelper::supportedSitesForElement($element), 'siteId');

        // Make sure the element actually supports the site it's being saved in
        if (! isset($supportedSites[$element->siteId])) {
            $this->resetElement($element);
            throw new UnsupportedSiteException($element, $element->siteId,
                'Attempting to save an element in an unsupported site.');
        }

        // If the element only supports a single site, ensure it's enabled for that site
        if (count($supportedSites) === 1 && ! $element->getEnabledForSite()) {
            $element->enabled = false;
            $element->setEnabledForSite(true);
        }

        // If we're skipping validation, at least make sure the title is valid
        if (! $runValidation && $element::hasTitles()) {
            $element->validate('title');

            if ($element->errors()->has('title')) {
                // Set a default title
                $element->title = $isNewElement
                    ? t('New {type}', ['type' => $element::displayName()])
                    : $element::displayName().' '.$element->id;
            }
        }

        $fieldLayout = $element->getFieldLayout();
        $dirtyFields = $element->getDirtyFields();

        // Get the element's site record
        if (! $isNewElement && ! $element->isNewForSite) {
            $siteSettingsRecord = ElementSiteSettings::query()
                ->where('elementId', $element->id)
                ->where('siteId', $element->siteId)
                ->first();
        }

        $element->isNewForSite = $siteSettingsRecord === null;

        // Validate
        if ($runValidation) {
            // If we're propagating, only validate changed custom fields,
            // unless we're enabling this element
            if ($element->propagating && ! (
                $element->getIsDerivative() &&
                $element->getIsDraft() &&
                $element->getEnabledForSite() &&
                ! $element->getCanonical()->getEnabledForSite())
            ) {
                $names = array_map(
                    fn (string $handle) => "field:$handle",
                    array_unique(array_merge($dirtyFields, $element->getModifiedFields())),
                );
            } else {
                $names = null;
            }

            if (($names === null || ! empty($names)) && ! $element->validate($names)) {
                Log::info('Element not saved due to validation error: '.print_r($element->errors, true), [__METHOD__]);
                $this->resetElement($element);

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
            &$siteSettingsRecord,
        ) {
            // Figure out whether we will be updating the search index (and memoize that for nested element saves)
            $oldUpdateSearchIndex = $this->updateSearchIndex;
            $updateSearchIndex = $this->updateSearchIndex = $updateSearchIndex ?? $this->updateSearchIndex ?? true;

            $newSiteIds = $element->newSiteIds;
            $element->newSiteIds = [];

            DB::beginTransaction();

            try {
                $this->updateModel($element, $isNewElement, $forceTouch);

                // Save the element’s site settings record
                if ($siteSettingsRecord === null) {
                    // First time we've saved the element for this site
                    $siteSettingsRecord = new ElementSiteSettings;
                    $siteSettingsRecord->elementId = $element->id;
                    $siteSettingsRecord->siteId = $element->siteId;
                }

                $title = $element::hasTitles() ? $element->title : null;
                $siteSettingsRecord->title = $title !== null && $title !== '' ? $title : null;
                $siteSettingsRecord->slug = $element->slug;
                $siteSettingsRecord->uri = $element->uri;

                // Avoid `enabled` getting marked as dirty if it’s not really changing
                $enabledForSite = $element->getEnabledForSite();
                if (! $siteSettingsRecord->exists || $siteSettingsRecord->enabled !== $enabledForSite) {
                    $siteSettingsRecord->enabled = $enabledForSite;
                }

                // Update our list of dirty attributes
                if ($this->trackChanges && ! $element->isNewForSite) {
                    array_push($this->dirtyAttributes, ...array_keys(Arr::only($siteSettingsRecord->getDirty(), [
                        'slug',
                        'uri',
                    ])));
                    if ($siteSettingsRecord->isDirty('enabled')) {
                        $this->dirtyAttributes[] = 'enabledForSite';
                    }
                }

                $saveContent = $saveContent || $element->isNewForSite;
                $generatedFields = $fieldLayout?->getGeneratedFields() ?? [];

                if ($saveContent || ! empty($dirtyFields) || ! empty($generatedFields)) {
                    $oldContent = $siteSettingsRecord->content ?? []; // we'll need that if we're not saving all the content
                    if (is_string($oldContent)) {
                        $oldContent = $oldContent !== '' ? Json::decode($oldContent) : [];
                    }

                    $content = [];

                    if ($fieldLayout) {
                        $validUids = [];

                        foreach ($fieldLayout->getCustomFields() as $field) {
                            $validUids[$field->layoutElement->uid] = true;

                            if (($saveContent || in_array($field->handle, $dirtyFields)) && $field::dbType() !== null) {
                                $value = $element->getFieldValue($field->handle);
                                if ($element->isNewForSite && $field->isValueEmpty($value, $element)) {
                                    // don't store empty values if element is new for site
                                    // https://github.com/craftcms/cms/issues/16797
                                    continue;
                                }
                                $serializedValue = $field->serializeValueForDb($value, $element);
                                if ($serializedValue !== null) {
                                    $content[$field->layoutElement->uid] = $serializedValue;
                                } elseif (! $saveContent) {
                                    // if serialized value is null, and we're not saving all the content,
                                    // we need to register the fact that the new value is empty
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

                    // if we're only saving dirty fields, merge in the existing values,
                    // excluding any UUIDs that are no longer valid (see https://github.com/craftcms/cms/issues/17768)
                    if (! $saveContent && $oldContent) {
                        foreach ($oldContent as $uid => $value) {
                            if (! isset($content[$uid]) && isset($validUids[$uid])) {
                                $content[$uid] = $value;
                            }
                        }
                    }

                    $siteSettingsRecord->content = $content ?: null;
                }

                // Save the site settings record
                if (! $siteSettingsRecord->save()) {
                    $this->resetElement($element);

                    throw new Exception('Couldn’t save elements’ site settings record.');
                }

                $element->siteSettingsId = $siteSettingsRecord->id;

                // Set all of the dirty attributes on the element, in case an event listener wants to know
                if ($this->trackChanges) {
                    array_push($this->dirtyAttributes, ...$element->getDirtyAttributes());
                    $element->setDirtyAttributes($this->dirtyAttributes, false);
                }

                // It is now officially saved
                $element->afterSave($isNewElement);

                // Update the list of dirty attributes
                $this->dirtyAttributes = $element->getDirtyAttributes();

                /** @var array<int,ElementInterface> $siteElements */
                $siteElements = [];
                /** @var array<int,ElementSiteSettings> $siteSettingsRecords */
                $siteSettingsRecords = [];

                // Update the element across the other sites?
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
                            // Skip the initial site
                            if ($siteId !== $element->siteId) {
                                $siteElement = $siteElements[$siteId] ?? false;
                                $siteElementRecord = null;
                                if (! app(PropagateElementAction::class)->handle(
                                    $element,
                                    $supportedSites,
                                    $siteId,
                                    $siteElement,
                                    crossSiteValidate: $runValidation && $crossSiteValidate,
                                    siteSettingsRecord: $siteElementRecord,
                                )) {
                                    throw new InvalidArgumentException;
                                }

                                $siteElements[$siteId] = $siteElement;
                                $siteSettingsRecords[$siteId] = $siteElementRecord;
                            }
                        }
                    }
                }

                // Save the generated fields after the element has been fully propagated,
                // so Matrix/CB/etc. have had a chance to save their data via afterElementPropagate()
                // (see https://github.com/craftcms/cms/issues/17938)
                if (! $element->propagating && ! empty($generatedFields)) {
                    $siteElements[$element->siteId] = $element;
                    $siteSettingsRecords[$element->siteId] = $siteSettingsRecord;

                    Event::listen(function (AfterPropagate $event) use ($element, $generatedFields, $siteElements, $siteSettingsRecords) {
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
                                $value = renderObjectTemplate($field['template'] ?? '', $siteElement);

                                // handle 'true'/'false'/'null'/int/float values
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

                // It's now fully saved and propagated
                if (
                    ! $element->propagating &&
                    ! $element->duplicateOf &&
                    ! $element->mergingCanonicalChanges
                ) {
                    $element->afterPropagate($isNewElement);

                    // Track this element in bulk operations
                    BulkOps::trackElement($element);
                }

                DB::commit();
            } catch (Throwable $e) {
                DB::rollBack();

                $this->resetElement($element);
                $element->dateUpdated = $this->originalDateUpdated;

                if ($e instanceof InvalidArgumentException) {
                    return false;
                }

                throw $e;
            } finally {
                $this->updateSearchIndex = $oldUpdateSearchIndex;
                $element->newSiteIds = $newSiteIds;
            }

            if (! $element->propagating) {
                // Delete the rows that don't need to be there anymore
                if (! $isNewElement) {
                    $deleteCondition = fn (Builder $query) => $query
                        ->where('elementId', $element->id)
                        ->whereNotIn('siteId', array_keys($supportedSites));

                    DB::table(Table::ELEMENTS_SITES)->where($deleteCondition)->delete();
                    DB::table(Table::SEARCHINDEX)->where($deleteCondition)->delete();
                    DB::table(Table::SEARCHINDEXQUEUE)->where($deleteCondition)->delete();
                }

                // Invalidate any caches involving this element
                $this->elementCaches->invalidateForElement($element);
            }

            // Update search index
            if ($updateSearchIndex && ! $element->getIsRevision() && ! ElementHelper::isRevision($element)) {
                $searchableDirtyFields = array_filter(
                    $dirtyFields,
                    fn (string $handle) => $fieldLayout?->getFieldByHandle($handle)?->searchable,
                );

                if (
                    ! $this->trackChanges ||
                    ! empty($searchableDirtyFields) ||
                    ! empty(array_intersect($this->dirtyAttributes, ElementHelper::searchableAttributes($element)))
                ) {
                    event($event = new BeforeUpdateSearchIndex($element));

                    if ($event->isValid) {
                        $this->updateSearchIndex($element, $searchableDirtyFields, $propagate);
                    }
                }
            }

            // Update the changed attributes & fields
            if ($this->trackChanges) {
                $userId = Auth::user()->id;
                $timestamp = now();

                foreach ($this->dirtyAttributes as $attributeName) {
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

        event(new AfterSaveElement($element, $isNewElement));

        // Clear the element’s record of dirty fields
        $element->markAsClean();
        $this->resetElement($element);

        return true;
    }

    private function updateModel(ElementInterface $element, bool $isNewElement, bool $forceTouch): void
    {
        // No need to save the element record multiple times
        if ($element->propagating) {
            return;
        }

        // Get the element record
        if (! $isNewElement) {
            $elementModel = ElementModel::find($element->id);

            if (! $elementModel) {
                $this->resetElement($element);

                throw new ElementNotFoundException("No element exists with the ID '$element->id'");
            }
        } else {
            $elementModel = new ElementModel;
            $elementModel->type = $element::class;
        }

        // Set the attributes
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

        if ($isNewElement) {
            if (isset($element->dateCreated)) {
                $elementModel->dateCreated = Query::prepareDateForDb($element->dateCreated);
            }
            if (isset($element->dateUpdated)) {
                $elementModel->dateUpdated = Query::prepareDateForDb($element->dateUpdated);
            }
        } elseif (! $element->resaving || $forceTouch) {
            // Force a new dateUpdated value
            $elementModel->dateUpdated = now();
        }

        // Update our list of dirty attributes
        if ($this->trackChanges) {
            array_push($this->dirtyAttributes, ...array_keys(Arr::only($elementModel->getDirty(), [
                'fieldLayoutId',
                'enabled',
                'archived',
            ])));
        }

        // Save the element record
        $elementModel->save();

        $dateCreated = DateTimeHelper::toDateTime($elementModel->dateCreated);

        if ($dateCreated === false) {
            $this->resetElement($element);

            throw new Exception('There was a problem calculating dateCreated.');
        }

        $dateUpdated = DateTimeHelper::toDateTime($elementModel->dateUpdated);

        if ($dateUpdated === false) {
            throw new Exception('There was a problem calculating dateUpdated.');
        }

        // Save the new dateCreated and dateUpdated dates on the model
        $element->dateCreated = $dateCreated;
        $element->dateUpdated = $dateUpdated;

        if ($isNewElement) {
            // Save the element ID on the element model
            $element->id = $elementModel->id;

            // If there's a temp ID, update the URI
            if ($element->tempId && $element->uri) {
                $element->uri = str_replace($element->tempId, (string) $element->id, $element->uri);
                $element->tempId = null;
            }
        }
    }

    private function updateSearchIndex(
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
                $this->updateSearchIndex($owner, [$field->handle], $propagate, true);
                $this->elementCaches->invalidateForElement($owner);
            }
        }
    }

    private function resetElement(ElementInterface $element): void
    {
        $element->firstSave = $this->originalFirstSave;
        $element->isNewForSite = $this->originalIsNewForSite;
        $element->propagateAll = $this->originalPropagateAll;
    }
}
