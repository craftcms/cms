<?php

namespace craft\elements\conditions\categories;

use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\elements\Category;
use CraftCms\Cms\Condition\Contracts\ConditionInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementQueryConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Support\Arr;
use CraftCms\Yii2Adapter\Element\Queries\CategoryQuery;
use Illuminate\Contracts\Database\Query\Builder;
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
    public static function isSelectableForCondition(ConditionInterface $condition): bool
    {
        if (!$condition instanceof CategoryCondition) {
            return false;
        }

        // Exclude from category group sources
        if (isset($condition->sourceKey) && str_starts_with($condition->sourceKey, 'group:')) {
            return false;
        }

        return true;
    }

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
    public function modifyQuery(Builder $query, ElementQuery $elementQuery): void
    {
        $categories = Craft::$app->getCategories();
        CategoryQuery::applyGroupId($query, $this->paramValue(fn(string $uid) => $categories->getGroupByUid($uid)->id ?? null));
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
