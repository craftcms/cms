<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Conditions;

use CraftCms\Cms\Condition\BaseMultiSelectConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\EntryQuery;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\UserGroups;
use Override;

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
    public function getLabel(): string
    {
        return t('Author Group');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['authorGroup', 'authorGroupId'];
    }

    #[Override]
    public static function isSelectable(): bool
    {
        return UserGroups::getAllGroups()->isNotEmpty();
    }

    protected function options(): array
    {
        return UserGroups::getAllGroups()->pluck('name', 'uid')->all();
    }

    /** @param EntryQuery<Entry> $query */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        $query->authorGroupId($this->paramValue(fn ($uid) => UserGroups::getGroupByUid($uid)->id ?? null));
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var Entry $element */
        $groups = $element->getAuthor()?->getGroups() ?? [];
        $groupUids = Arr::pluck($groups, 'uid');

        return $this->matchValue($groupUids);
    }
}
