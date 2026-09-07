<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Conditions;

use CraftCms\Cms\Condition\BaseMultiSelectConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementQueryConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Element\Queries\UserQuery;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Contracts\Database\Query\Builder;

use function CraftCms\Cms\t;

class AffiliatedSiteConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface, ElementQueryConditionRuleInterface
{
    public function getLabel(): string
    {
        return t('Affiliated Site');
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

    public function modifyQuery(Builder $query, ElementQuery $elementQuery): void
    {
        UserQuery::applyAffiliatedSiteId($query, $this->paramValue(fn ($uid) => Sites::getSiteByUid($uid, true)->id ?? null));
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var User $element */
        return $this->matchValue($element->getAffiliatedSite()?->uid);
    }
}
