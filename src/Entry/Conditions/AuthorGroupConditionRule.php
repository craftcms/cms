<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Conditions;

use CraftCms\Cms\Condition\BaseMultiSelectConditionRule;
use CraftCms\Cms\Condition\Contracts\ConditionInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementQueryConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\EntryQuery;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\UserGroups;
use Illuminate\Database\Query\Builder;
use Override;

use function CraftCms\Cms\t;

/**
 * Author group condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 4.0.0
 */
class AuthorGroupConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface, ElementQueryConditionRuleInterface
{
    public static function isSelectableForCondition(ConditionInterface $condition): bool
    {
        if (! $condition instanceof EntryCondition) {
            return false;
        }

        return true;
    }

    public function getLabel(): string
    {
        return t('Author Group');
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

    public function modifyQuery(Builder $query, ElementQueryInterface $elementQuery): void
    {
        EntryQuery::applyAuthorGroupId($query, $this->paramValue(fn ($uid) => UserGroups::getGroupByUid($uid)->id ?? null));
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var Entry $element */
        $groups = $element->getAuthor()?->getGroups() ?? [];
        $groupUids = Arr::pluck($groups, 'uid');

        return $this->matchValue($groupUids);
    }
}
