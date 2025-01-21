<?php

namespace craft\elements\conditions\users;

use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\UserQuery;
use craft\elements\User;
use craft\models\Site;

/**
 * Site condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.6.0
 */
class AffiliatedSiteConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Craft::t('app', 'Affiliated Site');
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return ['affiliatedSite', 'affiliatedSiteId'];
    }

    /**
     * @inheritdoc
     */
    protected function options(): array
    {
        return array_map(fn(Site $site) => [
            'label' => $site->getUiLabel(),
            'value' => $site->uid,
        ], Craft::$app->getSites()->getAllSites());
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        $sites = Craft::$app->getSites();
        /** @var UserQuery $query */
        $query->affiliatedSiteId($this->paramValue(fn($uid) => $sites->getSiteByUid($uid, true)->id ?? null));
    }

    /**
     * @inheritdoc
     */
    public function matchElement(ElementInterface $element): bool
    {
        /** @var User $element */
        return $this->matchValue($element->getAffiliatedSite()?->uid);
    }
}
