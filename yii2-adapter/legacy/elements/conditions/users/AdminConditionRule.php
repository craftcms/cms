<?php

namespace craft\elements\conditions\users;

/**
 * Admin condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\User\Conditions\AdminConditionRule} instead.
 */
class AdminConditionRule extends \CraftCms\Cms\User\Conditions\AdminConditionRule
{
    use \craft\base\LegacyEventConstants;
}
