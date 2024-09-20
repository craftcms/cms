<?php

namespace craft\elements\conditions;

use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use craft\models\Site;
use craft\models\SiteGroup;
use Illuminate\Support\Collection;

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
        return Craft::t('app', 'Site Group');
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
        $sitesService = Craft::$app->getSites();
        return Collection::make($sitesService->getAllGroups())
            ->filter(fn(SiteGroup $group) => !empty($sitesService->getEditableSitesByGroupId($group->id)))
            ->keyBy(fn(SiteGroup $group) => $group->uid)
            ->map(fn(SiteGroup $group) => $group->getName())
            ->all();
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        $sitesService = Craft::$app->getSites();
        $siteIds = Collection::make((array)$this->paramValue())
            ->map(fn(string $uid) => $sitesService->getGroupByUid($uid))
            ->filter(fn(?SiteGroup $group) => $group !== null)
            ->map(fn(SiteGroup $group) => $sitesService->getEditableSitesByGroupId($group->id))
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
