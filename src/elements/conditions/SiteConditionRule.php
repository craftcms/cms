<?php

namespace craft\elements\conditions;

use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ArrayHelper;

/**
 * Site condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class SiteConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Craft::t('app', 'Site');
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
        $sites = Craft::$app->getSites()->getEditableSites();
        return ArrayHelper::map($sites, 'uid', 'name');
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        $sites = Craft::$app->getSites();
        $query->siteId($this->paramValue(fn($uid) => $sites->getSiteByUid($uid, true)->id ?? null));
    }

    /**
     * @inheritdoc
     */
    public function matchElement(ElementInterface $element): bool
    {
        return $this->matchValue($element->getSite()->uid);
    }
}
