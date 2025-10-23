<?php

namespace craft\elements\conditions;

use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Site\Data\SiteGroup;
use CraftCms\Cms\Support\Facades\SiteGroups;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Support\Collection;
use function CraftCms\Cms\t;

/**
 * Site Group condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.4.0
 */
class SiteGroupConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return t('Site Group');
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return ['site', 'siteId'];
    }

    /**
     * @inheritdoc
     */
    protected function options(): array
    {
        return SiteGroups::getAllGroups()
            ->filter(fn(SiteGroup $group) => Sites::getEditableSitesByGroupId($group->id)->isNotEmpty())
            ->keyBy(fn(SiteGroup $group) => $group->uid)
            ->map(fn(SiteGroup $group) => $group->getName())
            ->all();
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        $siteIds = Collection::make((array) $this->paramValue())
            ->map(fn(string $uid) => SiteGroups::getGroupByUid($uid))
            ->filter(fn(?SiteGroup $group) => $group !== null)
            ->map(fn(SiteGroup $group) => Sites::getEditableSitesByGroupId($group->id))
            ->flatten(1)
            ->map(fn(Site $site) => $site->id)
            ->all();

        $query->siteId($siteIds);
    }

    /**
     * @inheritdoc
     */
    public function matchElement(ElementInterface $element): bool
    {
        return $this->matchValue($element->getSite()->getGroup()->uid);
    }
}
