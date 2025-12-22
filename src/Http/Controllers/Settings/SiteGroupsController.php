<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Site\Data\SiteGroup;
use CraftCms\Cms\Site\SiteGroups;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

final readonly class SiteGroupsController
{
    use RespondsWithFlash;

    public function __construct(
        private SiteGroups $siteGroups,
    ) {}

    public function store(SiteGroup $siteGroup): Response
    {
        $this->siteGroups->saveGroup($siteGroup);

        $data = $siteGroup->toArray();
        $data['name'] = t($data['name'], category: 'site');

        return back();
    }

    public function destroy(int $groupId): Response
    {
        /**
         * @TODO Better error message
         *
         * If you try to delete a group with sites associated with it, a good
         * error message is logged but not presented to the user. We should
         * surface the better error message but I don't want to change too
         * much about these methods at the moment.
         */
        if (! $this->siteGroups->deleteGroupById($groupId)) {
            return back()->with('error', t('Could not delete the group.'));
        }

        return to_route('craft.cp.settings.sites.index')
            ->with('success', t('Group deleted.'));
    }
}
