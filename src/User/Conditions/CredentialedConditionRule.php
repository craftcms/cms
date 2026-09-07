<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Conditions;

use CraftCms\Cms\Condition\BaseLightswitchConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementQueryConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Element\Queries\UserQuery;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Contracts\Database\Query\Builder;

use function CraftCms\Cms\t;

class CredentialedConditionRule extends BaseLightswitchConditionRule implements ElementConditionRuleInterface, ElementQueryConditionRuleInterface
{
    public function getLabel(): string
    {
        return t('Credentialed');
    }

    public function modifyQuery(Builder $query, ElementQuery $elementQuery): void
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
