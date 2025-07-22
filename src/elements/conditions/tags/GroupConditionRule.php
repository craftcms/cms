<?php

namespace craft\elements\conditions\tags;

use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\TagQuery;
use craft\elements\Tag;
use CraftCms\Cms\Support\Arr;

/**
 * Tag group condition rule.
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
        return Craft::t('app', 'Tag Group');
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
