<?php

namespace craft\elements\conditions\users;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * Credentialed condition rule.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\User\Conditions\CredentialedConditionRule} instead.
     */
    class CredentialedConditionRule
    {
    }
}

class_alias(\CraftCms\Cms\User\Conditions\CredentialedConditionRule::class, CredentialedConditionRule::class);
