<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Site\Data\SiteGroup;
use CraftCms\Cms\Site\SiteGroups;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

readonly class SiteGroupsController
{
    use RespondsWithFlash;

    public function __construct(
        private SiteGroups $siteGroups,
    ) {}

    public function store(Request $request): Response
    {
        $groupId = $request->input('id');

        if ($groupId) {
            abort_if(is_null($group = $this->siteGroups->getGroupById($groupId)), 400, "Invalid site group ID: $groupId");
        } else {
            $group = new SiteGroup;
        }

        $group->setName($request->input('name'));

        if (! $this->siteGroups->saveGroup($group)) {
            throw ValidationException::withMessages($group->errors()->getMessages());
        }

        return to_route('craft.cp.settings.sites.index', [
            'groupId' => $group->id,
        ])->with('success', t('Group saved.'));
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
