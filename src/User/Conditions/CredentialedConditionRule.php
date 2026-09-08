<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Conditions;

use CraftCms\Cms\Condition\BaseLightswitchConditionRule;
use CraftCms\Cms\Condition\Contracts\ConditionInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementQueryConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\UserQuery;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Database\Query\Builder;

use function CraftCms\Cms\t;

class CredentialedConditionRule extends BaseLightswitchConditionRule implements ElementConditionRuleInterface, ElementQueryConditionRuleInterface
{
    public static function isSelectableForCondition(ConditionInterface $condition): bool
    {
        if (! $condition instanceof UserCondition) {
            return false;
        }

        // Exclude from the Credentialed/Inactive sources
        if (in_array($condition->sourceKey, ['credentialed', 'inactive'])) {
            return false;
        }

        return true;
    }

    public function getLabel(): string
    {
        return t('Credentialed');
    }

    public function modifyQuery(Builder $query, ElementQueryInterface $elementQuery): void
    {
        if ($this->value) {
            $statuses = ['active', 'pending'];
        } else {
            $statuses = ['inactive'];
        }

        UserQuery::applyStatus($query, $statuses, $elementQuery);
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var User $element */
        return $this->matchValue($element->getIsCredentialed());
    }
}
