<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Conditions;

use craft\elements\db\UserQuery;
use CraftCms\Cms\Condition\BaseLightswitchConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\User\Elements\User;

use function CraftCms\Cms\t;

class CredentialedConditionRule extends BaseLightswitchConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return t('Credentialed');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['status'];
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var UserQuery $query */
        if ($this->value) {
            $query->status(['active', 'pending']);
        } else {
            $query->status('inactive');
        }
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var User $element */
        return $this->matchValue($element->getIsCredentialed());
    }
}
