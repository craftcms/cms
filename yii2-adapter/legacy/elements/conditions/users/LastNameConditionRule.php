<?php

namespace craft\elements\conditions\users;

/**
 * Last name condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\User\Conditions\LastNameConditionRule} instead.
 */
class LastNameConditionRule extends \CraftCms\Cms\User\Conditions\LastNameConditionRule
{
    use \craft\base\LegacyEventConstants;
}
