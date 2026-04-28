<?php

namespace craft\elements\conditions\tags;

use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\elements\db\TagQuery;
use craft\elements\Tag;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Support\Arr;
use function CraftCms\Cms\t;

/**
 * Tag group condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated in 6.0.0
 */
class GroupConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return t('Tag Group', category: 'yii2-adapter');
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
        $groups = Craft::$app->getTags()->getAllTagGroups();
        return Arr::pluck($groups, 'name', 'uid');
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var TagQuery $query */
        $tags = Craft::$app->getTags();
        $query->groupId($this->paramValue(fn($uid) => $tags->getTagGroupByUid($uid)->id ?? null));
    }

    /**
     * @inheritdoc
     */
    public function matchElement(ElementInterface $element): bool
    {
        /** @var Tag $element */
        return $this->matchValue($element->getGroup()->uid);
    }
}
