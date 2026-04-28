<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Conditions;

use CraftCms\Cms\Condition\BaseMultiSelectConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Site\Data\SiteGroup;
use CraftCms\Cms\Support\Facades\SiteGroups;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Support\Collection;

use function CraftCms\Cms\t;

class SiteGroupConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return t('Site Group');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['site', 'siteId'];
    }

    protected function options(): array
    {
        return SiteGroups::getAllGroups()
            ->filter(fn (SiteGroup $group) => Sites::getEditableSitesByGroupId($group->id)->isNotEmpty())
            ->keyBy(fn (SiteGroup $group) => $group->uid)
            ->map(fn (SiteGroup $group) => $group->getName())
            ->all();
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        $siteIds = Collection::make((array) $this->paramValue())
            ->map(fn (string $uid) => SiteGroups::getGroupByUid($uid))
            ->filter(fn (?SiteGroup $group) => $group !== null)
            ->map(fn (SiteGroup $group) => Sites::getEditableSitesByGroupId($group->id))
            ->flatten(1)
            ->map(fn (Site $site) => $site->id)
            ->all();

        $query->siteId($siteIds);
    }

    public function matchElement(ElementInterface $element): bool
    {
        return $this->matchValue($element->getSite()->getGroup()->uid);
    }
}
