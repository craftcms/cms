<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Conditions;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Support\Facades\Sites;

class UserCondition extends ElementCondition
{
    /**
     * {@inheritdoc}
     */
    #[\Override]
    protected function selectableConditionRules(): array
    {
        $types = array_merge(parent::selectableConditionRules(), [
            AdminConditionRule::class,
            CredentialedConditionRule::class,
            EmailConditionRule::class,
            FirstNameConditionRule::class,
            GroupConditionRule::class,
            LastLoginDateConditionRule::class,
            LastNameConditionRule::class,
        ]);

        if (! Cms::config()->useEmailAsUsername) {
            $types[] = UsernameConditionRule::class;
        }

        if (Sites::isMultiSite()) {
            $types[] = AffiliatedSiteConditionRule::class;
        }

        return $types;
    }
}
