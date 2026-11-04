<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp;

use CraftCms\Cms\Edition;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Data\UserGroup;
use CraftCms\Cms\User\UserGroups;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;

/**
 * Where nav customizations are kept, and which one applies to a user.
 *
 * Admins lay the nav out in project config, so navigations travel with the
 * rest of a site's structure: a default navigation, and at most one for each
 * user group, kept under the group's UID. A user in several groups gets the
 * first of theirs, in the order admins put them in.
 *
 * @phpstan-import-type NavDiff from NavCustomizer
 *
 * @phpstan-type GroupNavigation array{userGroup: UserGroup, sortOrder: int, nav: array<mixed>}
 *
 * @since 6.0.0
 */
#[Singleton]
class CustomNavigations
{
    private const string PATH_DEFAULT = ProjectConfig::PATH_CP_NAV.'.default';

    private const string PATH_GROUPS = ProjectConfig::PATH_CP_NAV.'.groups';

    public function __construct(
        private readonly ProjectConfig $projectConfig,
        private readonly UserGroups $userGroups,
    ) {}

    /** @return array<mixed> */
    public function getDefault(): array
    {
        $nav = $this->projectConfig->get(self::PATH_DEFAULT);

        return is_array($nav) ? $nav : [];
    }

    /** @param NavDiff $nav */
    public function saveDefault(array $nav): void
    {
        if ($nav === []) {
            $this->projectConfig->remove(self::PATH_DEFAULT, 'Reset the default CP navigation');

            return;
        }

        $this->projectConfig->set(self::PATH_DEFAULT, $nav, 'Save the default CP navigation');
    }

    /**
     * The user groups' navigations, in the order they're matched in. Any whose
     * group has since been deleted are left out.
     *
     * @return Collection<int, GroupNavigation>
     */
    public function getGroupNavigations(): Collection
    {
        $navigations = $this->projectConfig->get(self::PATH_GROUPS);
        $groups = $this->userGroups->getAllGroups()->keyBy('uid');

        return collect(is_array($navigations) ? $navigations : [])
            ->map(fn (mixed $navigation, string $groupUid): ?array => ($group = $groups->get($groupUid)) === null ? null : [
                'userGroup' => $group,
                'sortOrder' => (int) ($navigation['sortOrder'] ?? 0),
                'nav' => is_array($navigation['nav'] ?? null) ? $navigation['nav'] : [],
            ])
            ->filter()
            ->sortBy('sortOrder')
            ->values();
    }

    /** @return GroupNavigation|null */
    public function getGroupNavigation(string $groupUid): ?array
    {
        return $this->getGroupNavigations()->first(fn (array $navigation) => $navigation['userGroup']->uid === $groupUid);
    }

    /** @param NavDiff $nav */
    public function saveGroupNavigation(UserGroup $group, array $nav): void
    {
        $navigations = $this->getGroupNavigations();
        $sortOrder = $this->getGroupNavigation((string) $group->uid)['sortOrder']
            ?? ($navigations->max('sortOrder') ?? 0) + 1;

        $this->projectConfig->set(sprintf('%s.%s', self::PATH_GROUPS, $group->uid), array_filter([
            'sortOrder' => $sortOrder,
            'nav' => $nav,
        ], fn ($value) => $value !== []), "Save the CP navigation for “{$group->name}”");
    }

    public function deleteGroupNavigation(string $groupUid): void
    {
        $this->projectConfig->remove(sprintf('%s.%s', self::PATH_GROUPS, $groupUid), 'Delete a CP navigation');
    }

    /** @param list<string> $groupUids The navigations’ user group UIDs, in their new order */
    public function reorderGroupNavigations(array $groupUids): void
    {
        $saved = $this->getGroupNavigations()->map(fn (array $navigation) => $navigation['userGroup']->uid)->all();

        foreach (array_values(array_intersect($groupUids, $saved)) as $i => $groupUid) {
            $this->projectConfig->set(sprintf('%s.%s.sortOrder', self::PATH_GROUPS, $groupUid), $i + 1, 'Reorder CP navigations');
        }
    }

    /**
     * The navigation a user gets: the first one for a group they're in (Craft
     * Pro and up), layered over the default navigation, or the default one.
     *
     * @return array<mixed>
     */
    public function navigationFor(?CraftUser $user): array
    {
        $groupUids = $user === null || ! Edition::isAtLeast(Edition::Pro)
            ? []
            : array_map(fn (UserGroup $group) => $group->uid, $user->asElement()->getGroups());

        if ($groupUids !== []) {
            $navigation = $this->getGroupNavigations()->first(
                fn (array $navigation) => in_array($navigation['userGroup']->uid, $groupUids, true),
            );

            if ($navigation !== null) {
                return NavCustomizer::layer($this->getDefault(), $navigation['nav']);
            }
        }

        return $this->getDefault();
    }
}
