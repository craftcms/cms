<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Conditions;

use craft\base\ElementInterface;
use craft\elements\db\EntryQuery;
use CraftCms\Cms\Condition\BaseMultiSelectConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\UserGroups;

use function CraftCms\Cms\t;

/**
 * Author group condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 4.0.0
 */
class AuthorGroupConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return t('Author Group');
    }

    /**
     * {@inheritdoc}
     */
    public function getExclusiveQueryParams(): array
    {
        return ['authorGroup', 'authorGroupId'];
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function isSelectable(): bool
    {
        return UserGroups::getAllGroups()->isNotEmpty();
    }

    /**
     * {@inheritdoc}
     */
    protected function options(): array
    {
        return UserGroups::getAllGroups()->pluck('name', 'uid')->all();
    }

    /**
     * {@inheritdoc}
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var EntryQuery $query */
        $query->authorGroupId($this->paramValue(fn ($uid) => UserGroups::getGroupByUid($uid)->id ?? null));
    }

    /**
     * {@inheritdoc}
     */
    public function matchElement(ElementInterface $element): bool
    {
        /** @var Entry $element */
        $groups = $element->getAuthor()?->getGroups() ?? [];
        $groupUids = Arr::pluck($groups, 'uid');

        return $this->matchValue($groupUids);
    }
}
