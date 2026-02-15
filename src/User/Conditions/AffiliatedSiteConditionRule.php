<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Conditions;

use craft\base\ElementInterface;
use craft\elements\db\UserQuery;
use CraftCms\Cms\Condition\BaseMultiSelectConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;

use function CraftCms\Cms\t;

class AffiliatedSiteConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return t('Affiliated Site');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['affiliatedSite', 'affiliatedSiteId'];
    }

    protected function options(): array
    {
        return Sites::getAllSites()
            ->map(fn (Site $site) => [
                'label' => $site->getUiLabel(),
                'value' => $site->uid,
            ])
            ->all();
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var UserQuery $query */
        $query->affiliatedSiteId($this->paramValue(fn ($uid) => Sites::getSiteByUid($uid, true)->id ?? null));
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var User $element */
        return $this->matchValue($element->getAffiliatedSite()?->uid);
    }
}
