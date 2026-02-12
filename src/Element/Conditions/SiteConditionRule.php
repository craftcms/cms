<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Conditions;

use craft\base\ElementInterface;
use CraftCms\Cms\Condition\BaseMultiSelectConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\Sites;

use function CraftCms\Cms\t;

class SiteConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return t('Site');
    }

    /**
     * {@inheritdoc}
     */
    public function getExclusiveQueryParams(): array
    {
        return ['site', 'siteId'];
    }

    /**
     * {@inheritdoc}
     */
    protected function options(): array
    {
        return Sites::getEditableSites()
            ->map(fn (Site $site) => [
                'label' => $site->getUiLabel(),
                'value' => $site->uid,
            ])
            ->all();
    }

    /**
     * {@inheritdoc}
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        $query->siteId($this->paramValue(fn ($uid) => Sites::getSiteByUid($uid, true)->id ?? null));
    }

    /**
     * {@inheritdoc}
     */
    public function matchElement(ElementInterface $element): bool
    {
        return $this->matchValue($element->getSite()->uid);
    }
}
