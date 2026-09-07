<?php

namespace craft\elements\conditions\categories;

use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\elements\Category;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementQueryConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Support\Arr;
use CraftCms\Yii2Adapter\Element\Queries\CategoryQuery;
use Illuminate\Database\Query\Builder;
use function CraftCms\Cms\t;

/**
 * Category group condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated in 6.0.0
 */
class GroupConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface, ElementQueryConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return t('Category Group', category: 'yii2-adapter');
    }

    /**
     * @inheritdoc
     */
    protected function options(): array
    {
        $groups = Craft::$app->getCategories()->getAllGroups();
        return Arr::pluck($groups, 'name', 'uid');
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(Builder $query): void
    {
        $categories = Craft::$app->getCategories();
        /** @var CategoryQuery $query */
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
