<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Conditions;

use craft\elements\db\UserQuery;
use CraftCms\Cms\Condition\BaseLightswitchConditionRule;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\User\Elements\User;

use function CraftCms\Cms\t;

class AdminConditionRule extends BaseLightswitchConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return t('Admin');
    }

    #[\Override]
    public static function isSelectable(): bool
    {
        return Edition::isAtLeast(Edition::Pro);
    }

    public function getExclusiveQueryParams(): array
    {
        return ['admin'];
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var UserQuery $query */
        $query->admin($this->value);
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var User $element */
        return $this->matchValue($element->admin);
    }
}
