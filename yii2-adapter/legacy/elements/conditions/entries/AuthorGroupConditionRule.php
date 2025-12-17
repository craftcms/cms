<?php

namespace craft\elements\conditions\entries;

use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\EntryQuery;
use craft\elements\Entry;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\UserGroups;
use function CraftCms\Cms\t;

/**
 * Author group condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class AuthorGroupConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return t('Author Group');
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return ['authorGroup', 'authorGroupId'];
    }

    /**
     * @inheritdoc
     */
    public static function isSelectable(): bool
    {
        return UserGroups::getAllGroups()->isNotEmpty();
    }

    /**
     * @inheritdoc
     */
    protected function options(): array
    {
        return UserGroups::getAllGroups()->pluck('name', 'uid')->all();
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var EntryQuery $query */
        $query->authorGroupId($this->paramValue(fn($uid) => UserGroups::getGroupByUid($uid)->id ?? null));
    }

    /**
     * @inheritdoc
     */
    public function matchElement(ElementInterface $element): bool
    {
        /** @var Entry $element */
        $groups = $element->getAuthor()?->getGroups() ?? [];
        $groupUids = Arr::pluck($groups, 'uid');
        return $this->matchValue($groupUids);
    }
}
