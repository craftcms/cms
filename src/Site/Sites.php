<?php

declare(strict_types=1);

namespace CraftCms\Cms\Site;

use Craft;
use craft\base\ElementInterface;
use craft\elements\Asset;
use craft\helpers\Queue;
use craft\queue\jobs\PropagateElements;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Site\Events\ApplyingSiteDelete;
use CraftCms\Cms\Site\Events\DeletingSite;
use CraftCms\Cms\Site\Events\PrimarySiteChanged;
use CraftCms\Cms\Site\Events\ReorderingSites;
use CraftCms\Cms\Site\Events\SavingSite;
use CraftCms\Cms\Site\Events\SiteDeleted;
use CraftCms\Cms\Site\Events\SiteSaved;
use CraftCms\Cms\Site\Events\SitesReordered;
use CraftCms\Cms\Site\Exceptions\SiteNotFoundException;
use CraftCms\Cms\Site\Models\Site as SiteModel;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\SiteGroups;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\Updates\Updates;
use Exception;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;
use Tpetry\QueryExpressions\Language\Alias;

use function CraftCms\Cms\maxPowerCaptain;

#[Singleton]
final class Sites
{
    /**
     * This value can be configured as needed, but exists as a safeguard against performance issues.
     *
     * ::: warning
     * Craft’s multi-site support is not designed to be infinitely scalable.
     * Increase this limit at your own risk!
     * :::
     *
     * @var int The maximum number of sites that can be created.
     */
    public int $maxSites = 100;

    /**
     * @var ?Collection<int>
     *
     * @see getEditableSiteIds()
     */
    private ?Collection $editableSiteIds = null;

    /**
     * @var ?Collection<Site>
     *
     * @see getSiteById()
     */
    private ?Collection $allSitesById = null;

    /**
     * @var ?Collection<Site>
     *
     * @see getSiteById()
     */
    private ?Collection $enabledSitesById = null;

    /**
     * @var Site|null the current site
     *
     * @see getCurrentSite()
     * @see setCurrentSite()
     */
    private ?Site $currentSite = null;

    /**
     * @see getPrimarySite()
     */
    private ?Site $primarySite = null;

    private bool $isMultiSite;

    private bool $isMultiSiteWithTrashed;

    public function __construct(
        private readonly ProjectConfig $projectConfig,
        private readonly Updates $updates,
    ) {
        $this->loadAllSites();
    }

    public function isMultiSite(bool $refresh = false, bool $withTrashed = false): bool
    {
        if ($withTrashed) {
            return $this->isMultiSiteWithTrashed($refresh);
        }

        if ($refresh) {
            unset($this->isMultiSite);
        }

        return $this->isMultiSite ??= $this->getAllSites(true)->count() > 1;
    }

    public function isMultiSiteWithTrashed(bool $refresh = false): bool
    {
        if ($refresh) {
            unset($this->isMultiSiteWithTrashed);
        }

        return $this->isMultiSiteWithTrashed ??= DB::table(
            table: DB::table(Table::SITES)
                ->selectRaw('1')
                ->limit(2),
            as: 'x'
        )->count() !== 1;
    }

    /**
     * @return Collection<int>
     */
    public function getAllSiteIds(?bool $withDisabled = null): Collection
    {
        return $this->allSites($withDisabled)->pluck('id')->values();
    }

    /**
     * Returns a site by it's UID.
     *
     *
     * @return Site the site
     *
     * @throws SiteNotFoundException if no sites exist
     */
    public function getSiteByUid(string $uid, ?bool $withDisabled = null): Site
    {
        $site = $this->allSites($withDisabled)->firstWhere('uid', $uid);

        if ($site === null) {
            throw new SiteNotFoundException('Site with UID ”'.$uid.'“ not found!');
        }

        return $site;
    }

    /**
     * Returns whether the current site has been set yet.
     */
    public function getHasCurrentSite(): bool
    {
        return isset($this->currentSite);
    }

    /**
     * Returns the current site.
     *
     * > [!NOTE]
     * > This will always return the primary site for control panel requests. To fetch the site the control panel
     * > is currently working with, based on the `site` query string param, use [[\craft\helpers\Cp::requestedSite()]].
     *
     * @return Site the current site
     *
     * @throws SiteNotFoundException if no sites exist
     */
    public function getCurrentSite(): Site
    {
        // Default to the primary site
        return $this->currentSite ?? ($this->currentSite = $this->getPrimarySite());
    }

    /**
     * Sets the current site.
     *
     * @param  Site|string|int|null  $site  the current site, or its handle/ID, or null
     *
     * @throws InvalidArgumentException if $site is invalid
     */
    public function setCurrentSite(mixed $site): void
    {
        // In case this was called from the constructor...
        $this->loadAllSites();

        if ($site === null) {
            $this->currentSite = null;

            return;
        }

        $this->currentSite = match (true) {
            $site instanceof Site => $site,
            is_numeric($site) => $this->getSiteById($site, false),
            default => $this->getSiteByHandle($site, false),
        };

        // Did something go wrong?
        if (! $this->currentSite) {
            // Fail silently if Craft isn't installed yet or is in the middle of updating
            if (Cms::isInstalled() && ! $this->updates->isCraftUpdatePending()) {
                throw new InvalidArgumentException('Invalid site: '.$site);
            }

            return;
        }

        // Update the app language if this is a site request
        // (make sure the request component has been initialized first so we don't create an infinite loop)
        if (! request()->isCpRequest()) {
            app()->setLocale($this->currentSite->getLanguage());
        }

        // Set the CRAFT_SITE and CRAFT_SITE_UPPER env vars
        if (isset($this->currentSite->handle)) {
            $_SERVER['CRAFT_SITE'] = $this->currentSite->handle;
            $_SERVER['CRAFT_SITE_UPPER'] = str($this->currentSite->handle)->snake()->upper()->value();
        } else {
            unset($_SERVER['CRAFT_SITE'], $_SERVER['CRAFT_SITE_UPPER']);
        }
    }

    /**
     * Returns the primary site. The primary site is whatever is listed first in Settings > Sites in the
     * control panel.
     *
     * @return Site The primary site
     *
     * @throws SiteNotFoundException if no sites exist
     */
    public function getPrimarySite(): Site
    {
        if (! isset($this->primarySite)) {
            throw new SiteNotFoundException('No primary site exists');
        }

        return $this->primarySite;
    }

    /**
     * Returns all of the site IDs that are editable by the current user.
     *
     * @return Collection<int> All the editable sites’ IDs
     */
    public function getEditableSiteIds(): Collection
    {
        if (! $this->isMultiSite()) {
            return $this->getAllSiteIds(true);
        }

        if (isset($this->editableSiteIds)) {
            return $this->editableSiteIds;
        }

        $user = Auth::user();

        return $this->editableSiteIds = $this->getAllSites(true)->filter(fn (Site $site) => $user?->can("editSite:$site->uid"))->pluck('id')->values();
    }

    /**
     * Returns all of the site IDs that are editable by the current user in a certain section.
     *
     * @return Collection<int> All the editable sites’ IDs
     */
    public function getEditableSiteIdsForSection(Section $section): Collection
    {
        if (! $this->isMultiSite()) {
            return collect([$this->getPrimarySite()->id]);
        }

        return collect(array_keys($section->getSiteSettings()))
            ->intersect($this->getEditableSiteIds());
    }

    /**
     * Returns all sites.
     *
     * @return Collection<Site> All the sites
     */
    public function getAllSites(?bool $withDisabled = null): Collection
    {
        return $this->allSites($withDisabled);
    }

    /**
     * Returns all editable sites.
     *
     * @return Collection<Site> All the editable sites
     */
    public function getEditableSites(): Collection
    {
        $editableSiteIds = $this->getEditableSiteIds();

        return $this
            ->getAllSites()
            ->filter(fn (Site $site) => $editableSiteIds->contains($site->id))
            ->values();
    }

    /**
     * Returns sites by a group ID.
     *
     * @return Collection<Site>
     */
    public function getSitesByGroupId(int $groupId, ?bool $withDisabled = null): Collection
    {
        return $this->allSites($withDisabled)
            ->where('groupId', $groupId)
            ->sortBy('sortOrder', SORT_NUMERIC)
            ->values();
    }

    /**
     * Returns editable sites by a group ID.
     *
     * @return Collection<Site>
     */
    public function getEditableSitesByGroupId(int $groupId, ?bool $withDisabled = null): Collection
    {
        $editableSiteIds = $this->getEditableSiteIds()->flip();

        return $this
            ->getSitesByGroupId($groupId, $withDisabled)
            ->filter(fn (Site $site) => $editableSiteIds->has($site->id));
    }

    public function getTotalSites(): int
    {
        return $this->getAllSites()->count();
    }

    public function getTotalEditableSites(): int
    {
        return $this->getEditableSiteIds()->count();
    }

    public function getSiteById(int $siteId, ?bool $withDisabled = null): ?Site
    {
        return $this->allSites($withDisabled)[$siteId] ?? null;
    }

    public function getSiteByHandle(string $siteHandle, ?bool $withDisabled = null): ?Site
    {
        return $this->allSites($withDisabled)->firstWhere('handle', $siteHandle);
    }

    /**
     * Returns sites by their language.
     *
     * @return Collection<Site>
     */
    public function getSitesByLanguage(string $language, ?bool $withDisabled = null): Collection
    {
        return $this->allSites($withDisabled)
            ->where(fn (Site $site) => $site->getLanguage() === $language)
            ->values();
    }

    /**
     * Returns the number of sites that can be created, based on [[$maxSites]].
     *
     * @see $maxSites
     */
    public function getRemainingSites(): int
    {
        return max($this->maxSites - ($this->allSitesById?->count() ?? 0), 0);
    }

    /**
     * Saves a site.
     *
     * @param  Site  $site  The site to be saved
     *
     * @throws SiteNotFoundException if $site->id is invalid
     * @throws Throwable if reasons
     */
    public function saveSite(Site $site): bool
    {
        $isNewSite = ! $site->id;

        if ($isNewSite && ! $this->getRemainingSites()) {
            throw new Exception("Maximum number of sites cannot exceed $this->maxSites.");
        }

        $primarySite = $this->allSitesById->isEmpty() ? null : $this->getPrimarySite();

        event(new SavingSite(
            site: $site,
            isNew: $isNewSite,
            oldPrimarySiteId: $primarySite->id ?? null,
        ));

        if ($isNewSite) {
            $site->uid = Str::uuid()->toString();
            $site->sortOrder = DB::table(Table::SITES)
                ->whereNull('dateDeleted')
                ->max('sortOrder') + 1;
        } elseif (! $site->uid) {
            $site->uid = DB::table(Table::SITES)->uidById($site->id);
        }

        $this->projectConfig->set(
            path: ProjectConfig::PATH_SITES.".$site->uid",
            value: $site->getConfig(),
            message: "Save the “{$site->handle}” site",
        );

        // Now that we have a site ID, save it on the model
        if ($isNewSite) {
            $site->id = DB::table(Table::SITES)->idByUid($site->uid);
        }

        // If this just became the new primary site, update the old primary site's config
        if ($site->primary && $primarySite && $site->id !== $primarySite->id) {
            $this->projectConfig->set(
                path: ProjectConfig::PATH_SITES.".$primarySite->uid.primary",
                value: false,
                message: "Set the “{$primarySite->handle}” site not be primary",
            );
        }

        return true;
    }

    /**
     * Handle site changes.
     *
     * @throws Throwable
     */
    public function handleChangedSite(ConfigEvent $event): void
    {
        $siteUid = $event->tokenMatches[0];
        $data = $event->newValue;
        $groupUid = $data['siteGroup'];

        // Ensure we have the site group in place first
        $this->projectConfig->processConfigChanges(ProjectConfig::PATH_SITE_GROUPS.'.'.$groupUid);

        try {
            $oldPrimarySiteId = $this->getPrimarySite()->id;
        } catch (SiteNotFoundException) {
            $oldPrimarySiteId = null;
        }

        DB::beginTransaction();

        try {
            $siteModel = $this->getSiteModel($siteUid, true);
            $isNewSite = is_null($siteModel->id);
            $group = SiteGroups::getGroupByUid($groupUid);

            // Shared attributes
            $siteModel->uid = $siteUid;
            $siteModel->groupId = $group->id;
            $siteModel->primary = $data['primary'];
            $siteModel->enabled = $data['enabled'] ?? 'true';
            $siteModel->name = $data['name'];
            $siteModel->handle = $data['handle'];
            $siteModel->language = $data['language'];
            $siteModel->hasUrls = $data['hasUrls'];
            $siteModel->baseUrl = $data['baseUrl'];
            $siteModel->sortOrder = $data['sortOrder'];
            $siteModel->dateDeleted = null;
            $siteModel->save();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        // Clear caches
        $this->refreshSites();

        /** @var Site $site */
        $site = $this->getSiteById($siteModel->id);

        // Is this the current site?
        if ($this->currentSite && $this->currentSite->id === $site->id) {
            $this->currentSite = $site;
        }

        // Did the primary site just change?
        if ($oldPrimarySiteId && $data['primary'] && $site->id !== $oldPrimarySiteId) {
            $this->processNewPrimarySite($oldPrimarySiteId, $site->id);
        }

        // If the primary site is changing and the current site was the old primary, let's mark the new primary site as the current site.
        if ($this->currentSite && $this->currentSite->id === $oldPrimarySiteId && $this->currentSite->id !== $site->id && $data['primary']) {
            $this->currentSite = $site;
        }

        if ($isNewSite && $oldPrimarySiteId) {
            // Re-save most localizable element types
            // (skip entries because they only support specific sites)
            // (skip Matrix blocks because they will be re-saved when their owners are re-saved).
            $elementTypes = [
                Asset::class,
            ];

            foreach ($elementTypes as $elementType) {
                Queue::push(new PropagateElements([
                    'elementType' => $elementType,
                    'criteria' => [
                        'siteId' => $oldPrimarySiteId,
                    ],
                    'siteId' => $site->id,
                    'isNewSite' => true,
                ]));
            }
        }

        event(new SiteSaved(
            site: $site,
            isNew: $isNewSite,
            oldPrimarySiteId: $oldPrimarySiteId,
        ));

        // Invalidate all element caches
        Craft::$app->getElements()->invalidateAllCaches();
    }

    /**
     * Reorders sites.
     *
     * @param  int[]  $siteIds  The site IDs in their new order
     * @return bool Whether the sites were reordered successfully
     *
     * @throws Throwable if reasons
     */
    public function reorderSites(array $siteIds): bool
    {
        event(new ReorderingSites(siteIds: $siteIds));

        $uidsByIds = DB::table(Table::SITES)->uidsByIds($siteIds);

        foreach ($siteIds as $sortOrder => $siteId) {
            if (empty($uidsByIds[$siteId])) {
                continue;
            }

            $this->projectConfig->set(
                path: ProjectConfig::PATH_SITES.'.'.$uidsByIds[$siteId].'.sortOrder',
                value: $sortOrder + 1,
                message: 'Reorder sites',
            );
        }

        event(new SitesReordered(siteIds: $siteIds));

        return true;
    }

    /**
     * Deletes a site by its ID.
     *
     * @param  int  $siteId  The site ID to be deleted
     * @param  int|null  $transferContentTo  The site ID that should take over the deleted site’s contents
     * @return bool Whether the site was deleted successfully
     *
     * @throws Throwable if reasons
     */
    public function deleteSiteById(int $siteId, ?int $transferContentTo = null): bool
    {
        $site = $this->getSiteById($siteId);

        if (! $site) {
            return false;
        }

        return $this->deleteSite($site, $transferContentTo);
    }

    /**
     * Deletes a site.
     *
     * @param  Site  $site  The site to be deleted
     * @param  int|null  $transferContentTo  The site ID that should take over the deleted site’s contents
     * @return bool Whether the site was deleted successfully
     *
     * @throws Exception if $site is the primary site
     * @throws Throwable if reasons
     */
    public function deleteSite(Site $site, ?int $transferContentTo = null): bool
    {
        // Make sure this isn't the primary site
        if ($site->id === $this->primarySite->id) {
            throw new Exception('You cannot delete the primary site.');
        }

        event($event = new DeletingSite(site: $site, transferContentTo: $transferContentTo));

        if (! $event->isValid) {
            return false;
        }

        // TODO: Move this code into entries module, etc.
        // Get the section IDs that are enabled for this site
        $sectionIds = DB::table(Table::SECTIONS_SITES)
            ->where('siteId', $site->id)
            ->pluck('sectionId');

        // Figure out which ones are *only* enabled for this site
        $soloSectionIds = [];

        foreach ($sectionIds as $sectionId) {
            $sectionSiteSettings = Sections::getSectionSiteSettings($sectionId);

            if (count($sectionSiteSettings) === 1 && $sectionSiteSettings[0]->siteId === $site->id) {
                $soloSectionIds[] = $sectionId;
            }
        }

        // Did we find any?
        if (! empty($soloSectionIds)) {
            // Should we enable those for a different site?
            if ($transferContentTo !== null) {
                /** @var Site $transferContentToSite */
                $transferContentToSite = $this->getSiteById($transferContentTo);

                DB::table(Table::SECTIONS_SITES)
                    ->whereIn('sectionId', $soloSectionIds)
                    ->update([
                        'siteId' => $transferContentTo,
                        'dateUpdated' => now(),
                    ]);

                // Update the project config too
                $muteEvents = $this->projectConfig->muteEvents;
                $this->projectConfig->muteEvents = true;

                foreach ($this->projectConfig->get(ProjectConfig::PATH_SECTIONS) as $sectionUid => $sectionConfig) {
                    if (count($sectionConfig['siteSettings']) === 1 && isset($sectionConfig['siteSettings'][$site->uid])) {
                        $sectionConfig['siteSettings'][$transferContentToSite->uid] = Arr::pull($sectionConfig['siteSettings'],
                            $site->uid);
                        $this->projectConfig->set(
                            path: ProjectConfig::PATH_SECTIONS.'.'.$sectionUid,
                            value: $sectionConfig,
                            message: 'Prune site settings',
                        );
                    }
                }

                $this->projectConfig->muteEvents = $muteEvents;

                // Get all of the entry IDs in those sections
                $entryIds = DB::table(Table::ENTRIES)
                    ->whereIn('sectionId', $soloSectionIds)
                    ->pluck('id');

                if ($entryIds->isNotEmpty()) {
                    // Update the entry tables
                    DB::table(Table::ELEMENTS_SITES)
                        ->whereIn('elementId', $entryIds)
                        ->update([
                            'siteId' => $transferContentTo,
                            'dateUpdated' => now(),
                        ]);

                    DB::table(Table::RELATIONS)
                        ->whereIn('sourceId', $entryIds)
                        ->whereNotNull('sourceSiteId')
                        ->update([
                            'sourceSiteId' => $transferContentTo,
                            'dateUpdated' => now(),
                        ]);

                    // Nested entries
                    $nestedEntryIds = DB::table(Table::ENTRIES)
                        ->whereIn('primaryOwnerId', $entryIds)
                        ->pluck('id');

                    if ($nestedEntryIds->isNotEmpty()) {
                        DB::table(Table::ELEMENTS_SITES)
                            ->whereIn('elementId', $nestedEntryIds)
                            ->where('siteId', $transferContentTo)
                            ->delete();

                        DB::table(Table::ELEMENTS_SITES)
                            ->whereIn('elementId', $nestedEntryIds)
                            ->where('siteId', $site->id)
                            ->update([
                                'siteId' => $transferContentTo,
                                'dateUpdated' => now(),
                            ]);

                        DB::table(Table::RELATIONS)
                            ->whereIn('sourceId', $nestedEntryIds)
                            ->whereNotNull('sourceSiteId')
                            ->update([
                                'sourceSiteId' => $transferContentTo,
                                'dateUpdated' => now(),
                            ]);
                    }
                }
            } else {
                // Delete those sections
                foreach ($soloSectionIds as $sectionId) {
                    Sections::deleteSectionById($sectionId);
                }
            }
        }

        $this->projectConfig->remove(
            ProjectConfig::PATH_SITES.'.'.$site->uid,
            "Delete the “{$site->handle}” site",
        );

        return true;
    }

    /**
     * Handle a deleted Site.
     *
     * @throws Throwable
     */
    public function handleDeletedSite(ConfigEvent $event): void
    {
        $siteUid = $event->tokenMatches[0];
        $siteRecord = $this->getSiteModel($siteUid);

        if (! $siteRecord->id) {
            return;
        }

        /** @var Site $site */
        $site = $this->getSiteById($siteRecord->id);

        event(new ApplyingSiteDelete($site));

        DB::transaction(function () use ($siteRecord) {
            DB::table(Table::SITES)->softDelete($siteRecord->id);
        });

        // Refresh sites
        $this->refreshSites();

        // Invalidate all element caches
        Craft::$app->getElements()->invalidateAllCaches();

        // Was this the current site?
        if (isset($this->currentSite) && $this->currentSite->id === $site->id) {
            $this->setCurrentSite($this->primarySite);
        }

        event(new SiteDeleted($site));

        // Invalidate all element caches
        Craft::$app->getElements()->invalidateAllCaches();
    }

    /**
     * Restores a site by its ID.
     *
     * @param  int  $id  The site’s ID
     * @return bool Whether the site was restored successfully
     */
    public function restoreSiteById(int $id): bool
    {
        return (bool) DB::table(Table::SITES)->restore($id);
    }

    /**
     * Refresh the status of all sites based on the DB data.
     */
    public function refreshSites(): void
    {
        $this->allSitesById = null;
        $this->enabledSitesById = null;
        $this->editableSiteIds = null;
        unset($this->isMultiSite);
        unset($this->isMultiSiteWithTrashed);
        $this->loadAllSites();
        $this->isMultiSite(true);
    }

    private function loadAllSites(): void
    {
        if (isset($this->allSitesById)) {
            return;
        }

        $this->allSitesById = collect();
        $this->enabledSitesById = collect();

        if (! Cms::isInstalled(true)) {
            return;
        }

        $results = DB::table(Table::SITES, 's')
            ->select([
                's.id',
                's.groupId',
                's.name',
                's.handle',
                's.language',
                's.primary',
                's.enabled',
                's.hasUrls',
                's.baseUrl',
                's.sortOrder',
                's.uid',
                's.dateCreated',
                's.dateUpdated',
            ])
            ->join(new Alias(Table::SITEGROUPS, 'sg'), 'sg.id', 's.groupId')
            ->whereNull(['s.dateDeleted', 'sg.dateDeleted'])
            ->orderBy('sg.name')
            ->orderBy('s.sortOrder')
            ->orderBy('s.id')
            ->get();

        // Check for results because during installation, the transaction hasn't been committed yet.
        if ($results->isEmpty()) {
            return;
        }

        foreach ($results as $result) {
            $result->dateCreated = Date::parse($result->dateCreated);
            $result->dateUpdated = Date::parse($result->dateUpdated);

            $result = (array) $result;

            // @TODO: Use Site::from() when Codeception tests are removed
            Typecast::properties(Site::class, $result);

            $site = new Site(...$result);
            $this->allSitesById[$site->id] = $site;

            if ($site->getEnabled()) {
                $this->enabledSitesById[$site->id] = $site;
            }

            if ($site->primary) {
                $this->primarySite = $site;
            }
        }
    }

    /**
     * Returns all sites, or only enabled sites.
     *
     * @return Collection<Site>
     */
    private function allSites(?bool $withDisabled = null): Collection
    {
        if ($withDisabled === null) {
            $withDisabled = (
                app()->runningInConsole() ||
                (request()->isCpRequest() && Auth::check())
            );
        }

        return $withDisabled ? $this->allSitesById : $this->enabledSitesById;
    }

    /**
     * Gets a site record or creates a new one.
     *
     * @param  mixed  $idOrUid  ID or UID of the site group.
     * @param  bool  $withTrashed  Whether to include trashed sites in search
     */
    private function getSiteModel(mixed $idOrUid, bool $withTrashed = false): SiteModel
    {
        return SiteModel::query()
            ->when($withTrashed, fn (Builder $query) => $query->withTrashed())
            ->when(
                is_numeric($idOrUid),
                fn (Builder $query) => $query->where('id', $idOrUid),
                fn (Builder $query) => $query->where('uid', $idOrUid),
            )->first() ?? new SiteModel;
    }

    /**
     * Handles things that happen when there's a new primary site
     *
     * @throws Throwable
     */
    private function processNewPrimarySite(int $oldPrimarySiteId, int $newPrimarySiteId): void
    {
        maxPowerCaptain();

        DB::beginTransaction();

        try {
            DB::table(Table::SITES)
                ->where('id', $oldPrimarySiteId)
                ->update([
                    'primary' => false,
                    'dateUpdated' => now(),
                ]);

            DB::table(Table::SITES)
                ->where('id', $newPrimarySiteId)
                ->update([
                    'primary' => true,
                    'dateUpdated' => now(),
                ]);

            // Update all of the non-localized elements
            $nonLocalizedElementTypes = [];

            foreach (Craft::$app->getElements()->getAllElementTypes() as $elementType) {
                /** @var class-string<ElementInterface> $elementType */
                if (! $elementType::isLocalized()) {
                    $nonLocalizedElementTypes[] = $elementType;
                }
            }

            if (! empty($nonLocalizedElementTypes)) {
                // To be sure we don't hit any unique constraint database errors, first make sure there are no rows for
                // these elements that don't currently use the old primary site ID
                foreach ([Table::ELEMENTS_SITES, Table::SEARCHINDEX] as $table) {
                    DB::table($table, 't')
                        ->whereIn(
                            't.elementId',
                            DB::table(Table::ELEMENTS, 'e')
                                ->select('e.id')
                                ->whereIn('e.type', $nonLocalizedElementTypes),
                        )
                        ->whereNot('t.siteId', $oldPrimarySiteId)
                        ->delete();

                    DB::table($table, 't')
                        ->whereIn(
                            't.elementId',
                            DB::table(Table::ELEMENTS, 'e')
                                ->whereIn('e.type', $nonLocalizedElementTypes)
                                ->select('e.id'),
                        )
                        ->update(['siteId' => $newPrimarySiteId]);
                }
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        // Set the new primary site by forcing a reload from the DB.
        $this->refreshSites();

        event(new PrimarySiteChanged($this->primarySite));
    }
}
