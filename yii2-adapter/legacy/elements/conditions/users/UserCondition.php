<?php

namespace craft\elements\conditions\users;

use craft\elements\conditions\ElementCondition;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Facades\Sites;

/**
 * User query condition.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class UserCondition extends ElementCondition
{
    /**
     * @inheritdoc
     */
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

        if (!Cms::config()->useEmailAsUsername) {
            $types[] = UsernameConditionRule::class;
        }

        if (Sites::isMultiSite()) {
            $types[] = AffiliatedSiteConditionRule::class;
        }

        return $types;
    }
}
