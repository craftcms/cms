<?php

namespace CraftCms\Cms\Site;

use craft\base\MemoizableArray;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Site\Data\SiteGroup;
use CraftCms\Cms\Site\Events\ApplyingSiteGroupDelete;
use CraftCms\Cms\Site\Events\DeletedSiteGroup;
use CraftCms\Cms\Site\Events\DeletingSiteGroup;
use CraftCms\Cms\Site\Events\SavedSiteGroup;
use CraftCms\Cms\Site\Events\SavingSiteGroup;
use CraftCms\Cms\Site\Models\SiteGroup as SiteGroupModel;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

#[Singleton]
final class SiteGroups
{
    /**
     * @var MemoizableArray<SiteGroup>|null
     *
     * @see groups()
     */
    private ?MemoizableArray $groups = null;

    public function __construct(
        private readonly ProjectConfig $projectConfig,
    ) {}

    /**
     * Returns a memoizable array of all site groups.
     *
     * @return MemoizableArray<SiteGroup>
     */
    private function groups(): MemoizableArray
    {
        return $this->groups ??= new MemoizableArray(
            $this->createGroupQuery()->get()->all(),
            fn (object $result) => new SiteGroup(...(array) $result),
        );
    }

    /**
     * Returns all site groups.
     *
     * @return Collection<SiteGroup>
     */
    public function getAllGroups(): Collection
    {
        return collect($this->groups()->all());
    }

    public function getGroupById(int $groupId): ?SiteGroup
    {
        return $this->groups()->firstWhere('id', $groupId);
    }

    public function getGroupByUid(string $uid): ?SiteGroup
    {
        return $this->groups()->firstWhere('uid', $uid, true);
    }

    /**
     * @param  SiteGroup  $group  The site group to be saved
     * @return bool Whether the site group was saved successfully
     */
    public function saveGroup(SiteGroup $group): bool
    {
        $isNewGroup = ! $group->id;

        if (Event::hasListeners(SavingSiteGroup::class)) {
            Event::dispatch(new SavingSiteGroup($group, $isNewGroup));
        }

        if ($isNewGroup) {
            $group->uid = Str::uuid()->toString();
        } elseif (! $group->uid) {
            $group->uid = DB::table(Table::SITEGROUPS)->uidById($group->id);
        }

        $this->projectConfig->set(
            path: ProjectConfig::PATH_SITE_GROUPS.'.'.$group->uid,
            value: $group->getConfig(),
            message: "Save the “{$group->getName(false)}” site group",
        );

        // Now that we have an ID, save it on the model
        if ($isNewGroup) {
            $group->id = DB::table(Table::SITEGROUPS)->idByUid($group->uid);
        }

        return true;
    }

    /**
     * Handle site group change
     */
    public function handleChangedGroup(ConfigEvent $event): void
    {
        $data = $event->newValue;
        $uid = $event->tokenMatches[0];

        $groupModel = $this->getGroupModel($uid, true);
        $isNewGroup = $groupModel->exists;

        // If this is a new group, set the UID we want.
        if (! $groupModel->id) {
            $groupModel->uid = $uid;
        }

        $groupModel->name = $data['name'];
        $groupModel->dateDeleted = null;
        $groupModel->save();

        // Clear caches
        $this->refreshGroups();

        if (Event::hasListeners(SavedSiteGroup::class)) {
            Event::dispatch(new SavedSiteGroup($this->getGroupById($groupModel->id), $isNewGroup));
        }
    }

    /**
     * Handle site group getting deleted.
     */
    public function handleDeletedGroup(ConfigEvent $event): void
    {
        $uid = $event->tokenMatches[0];
        $groupModel = $this->getGroupModel($uid);

        if (! $groupModel->id) {
            return;
        }

        $group = $this->getGroupById($groupModel->id);

        if (Event::hasListeners(ApplyingSiteGroupDelete::class)) {
            Event::dispatch(new ApplyingSiteGroupDelete($group));
        }

        $groupModel->delete();

        // Clear caches
        $this->refreshGroups();

        if (Event::hasListeners(DeletedSiteGroup::class)) {
            Event::dispatch(new DeletedSiteGroup($group));
        }
    }

    /**
     * Deletes a site group by its ID.
     *
     * @param  int  $groupId  The site group’s ID
     * @return bool Whether the site group was deleted successfully
     */
    public function deleteGroupById(int $groupId): bool
    {
        if (! $group = $this->getGroupById($groupId)) {
            return false;
        }

        return $this->deleteGroup($group);
    }

    /**
     * Deletes a site group.
     *
     * @param  SiteGroup  $group  The site group
     * @return bool Whether the site group was deleted successfully
     */
    public function deleteGroup(SiteGroup $group): bool
    {
        if (Sites::getSitesByGroupId($group->id)->isNotEmpty()) {
            Log::warning('Attempted to delete a site group that still had sites assigned to it.', [__METHOD__]);

            return false;
        }

        if (! SiteGroupModel::find($group->id)) {
            return false;
        }

        if (Event::hasListeners(DeletingSiteGroup::class)) {
            Event::dispatch(new DeletingSiteGroup($group));
        }

        $this->projectConfig->remove(
            path: ProjectConfig::PATH_SITE_GROUPS.'.'.$group->uid,
            message: "Delete the “{$group->getName(false)}” site group"
        );

        return true;
    }

    public function refreshGroups(): void
    {
        $this->groups = null;
    }

    private function createGroupQuery(): QueryBuilder
    {
        return DB::table(Table::SITEGROUPS)
            ->select([
                'id',
                'name',
                'uid',
            ])
            ->whereNull('dateDeleted')
            ->orderBy('name');
    }

    /**
     * Gets a site group record or creates a new one.
     *
     * @param  mixed  $idOrUid  ID or UID of the site group.
     * @param  bool  $withTrashed  Whether to include trashed site groups in search
     */
    private function getGroupModel(mixed $idOrUid, bool $withTrashed = false): SiteGroupModel
    {
        return SiteGroupModel::query()
            ->when($withTrashed, fn (Builder $query) => $query->withTrashed())
            ->when(
                value: is_numeric($idOrUid),
                callback: fn (Builder $query) => $query->where('id', $idOrUid),
                default: fn (Builder $query) => $query->where('uid', $idOrUid),
            )
            ->first() ?? new SiteGroupModel;
    }
}
