<?php

declare(strict_types=1);

namespace CraftCms\Cms\Utility\Utilities;

use CraftCms\Cms\Ai\Guidance\Guidance;
use CraftCms\Cms\Ai\Models\AgentSkill;
use CraftCms\Cms\Ai\Skills\AgentSkills;
use CraftCms\Cms\Ai\Tasks\Assistant;
use CraftCms\Cms\Cp\Data\NavItem;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\EditUserScreens;
use CraftCms\Cms\Utility\Utility;
use Override;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\currentUserElement;
use function CraftCms\Cms\t;

/**
 * Edits the guidance the AI assistant follows, for every site and for each site the user can edit, and its skills.
 *
 * @since 6.0.0
 */
class AiGuidance extends Utility
{
    #[Override]
    public static function displayName(): string
    {
        return t('AI Guidance & Skills');
    }

    #[Override]
    public static function id(): string
    {
        return 'ai-guidance';
    }

    #[Override]
    public static function icon(): string
    {
        return 'sparkles';
    }

    /**
     * The utility’s two pages, Guidance and Skills, each with its own URL.
     */
    #[Override]
    public static function subpageTitle(): ?string
    {
        return self::isSkillsPage() ? t('Skills') : t('Guidance');
    }

    #[Override]
    public static function contentHtml(): string
    {
        $subnav = [
            new NavItem()->label(t('Guidance'))->href(Url::cpUrl('utilities/'.self::id()))->selected(! self::isSkillsPage()),
            new NavItem()->label(t('Skills'))->href(self::skillsUrl())->selected(self::isSkillsPage()),
        ];
        $subnav = array_map(static fn (NavItem $item): array => $item->toArray(), $subnav);

        if (self::isSkillsPage()) {
            return Html::tag('AiSkills', attributes: [
                ':subnav' => $subnav,
                'create-skill-url' => Url::cpUrl('ai-skills/new'),
                ':skills' => app(AgentSkills::class)->all()
                    ->map(fn (AgentSkill $skill): array => self::skillData($skill))
                    ->values()
                    ->all(),
            ]);
        }

        return Html::tag('AiGuidance', attributes: [
            ':subnav' => $subnav,
            ':scopes' => self::scopes(),
            // Only users who can use the assistant have the account screen for their own guidance.
            'personal-guidance-url' => currentUser()?->can(Assistant::PERMISSION)
                ? app(EditUserScreens::class)->url(currentUserElement(), EditUserScreens::AI_ASSISTANT)
                : null,
        ]);
    }

    /**
     * The URL of the Skills page.
     */
    public static function skillsUrl(): string
    {
        return Url::cpUrl('utilities/'.self::id().'/skills');
    }

    private static function isSkillsPage(): bool
    {
        return request()->route('extra') === 'skills';
    }

    /**
     * The guidance for every site, then for each site, if there’s more than one, or a single site still has its own
     * guidance, which the assistant follows. The user can only edit the guidance for the sites they can edit, and the
     * guidance for every site if they can edit every site.
     *
     * @return list<array{siteId: int|null, heading: string, body: string, editable: bool}>
     */
    public static function scopes(): array
    {
        $guidance = app(Guidance::class);
        $allSites = Sites::getAllSites();
        $showSites = Sites::isMultiSite()
            || $allSites->contains(static fn (Site $site): bool => $guidance->forSite($site->id) !== null);

        $scopes = [[
            'siteId' => null,
            'heading' => $showSites ? t('All sites') : t('Guidance'),
            'body' => $guidance->forAllSites() ?? '',
            'editable' => self::canEditGuidanceForAllSites(),
        ]];

        if (! $showSites) {
            return $scopes;
        }

        $editableSiteIds = Sites::getEditableSiteIds();

        $sites = $allSites->map(fn (Site $site): array => [
            'siteId' => $site->id,
            'heading' => $site->getName(),
            'body' => $guidance->forSite($site->id) ?? '',
            'editable' => $editableSiteIds->contains($site->id),
        ]);

        return [...$scopes, ...$sites->values()->all()];
    }

    /**
     * Returns whether the current user can edit the guidance for every site, which applies to content in every site:
     * only if they can edit every site.
     *
     * @internal
     */
    public static function canEditGuidanceForAllSites(): bool
    {
        return Sites::getAllSiteIds(true)->diff(Sites::getEditableSiteIds())->isEmpty();
    }

    /**
     * A skill as the utility lists and edits it.
     *
     * @return array{id: int, title: string, description: string, body: string, editUrl: string}
     *
     * @internal
     */
    public static function skillData(AgentSkill $skill): array
    {
        return [
            'id' => $skill->id,
            'title' => $skill->title,
            'description' => $skill->description,
            'body' => $skill->body,
            'editUrl' => Url::cpUrl("ai-skills/{$skill->id}"),
        ];
    }
}
