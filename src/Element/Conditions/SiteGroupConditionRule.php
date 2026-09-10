<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Conditions;

use CraftCms\Cms\Condition\BaseMultiSelectConditionRule;
use CraftCms\Cms\Condition\Contracts\ConditionInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementQueryConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Site\Data\SiteGroup;
use CraftCms\Cms\Support\Facades\SiteGroups;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

use function CraftCms\Cms\t;

class SiteGroupConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface, ElementQueryConditionRuleInterface
{
    public static function isSelectableForCondition(ConditionInterface $condition): bool
    {
        // Exclude from element query conditions
        if ($condition instanceof ElementCondition && $condition->forQuery) {
            return false;
        }

        return true;
    }

    public function getLabel(): string
    {
        return t('Site Group');
    }

    protected function options(): array
    {
        return SiteGroups::getAllGroups()
            ->filter(fn (SiteGroup $group) => Sites::getEditableSitesByGroupId($group->id)->isNotEmpty())
            ->keyBy(fn (SiteGroup $group) => $group->uid)
            ->map(fn (SiteGroup $group) => $group->getName())
            ->all();
    }

    public function modifyQuery(Builder $query, ElementQueryInterface $elementQuery): void
    {
        $siteIds = Collection::make((array) $this->paramValue())
            ->map(fn (string $uid) => SiteGroups::getGroupByUid($uid))
            ->filter(fn (?SiteGroup $group) => $group !== null)
            ->map(fn (SiteGroup $group) => Sites::getEditableSitesByGroupId($group->id))
            ->flatten(1)
            ->map(fn (Site $site) => $site->id)
            ->all();

        ElementQuery::applySiteId($query, $siteIds);
    }

    public function matchElement(ElementInterface $element): bool
    {
        return $this->matchValue($element->getSite()->getGroup()->uid);
    }
}
