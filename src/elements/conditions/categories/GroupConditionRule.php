<?php

namespace craft\elements\conditions\categories;

use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\Category;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\CategoryQuery;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ArrayHelper;

/**
 * Category group condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class GroupConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Craft::t('app', 'Category Group');
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return ['group', 'groupId'];
    }

    /**
     * @inheritdoc
     */
    protected function options(): array
    {
        $groups = Craft::$app->getCategories()->getAllGroups();
        return ArrayHelper::map($groups, 'uid', 'name');
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var CategoryQuery $query */
        $categories = Craft::$app->getCategories();
        $query->groupId($this->paramValue(fn(string $uid) => $categories->getGroupByUid($uid)->id ?? null));
    }

    /**
     * @inheritdoc
     */
    public function matchElement(ElementInterface $element): bool
    {
        /** @var Category $element */
        return $this->matchValue($element->getGroup()->uid);
    }
}
