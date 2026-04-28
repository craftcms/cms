<?php

namespace craft\elements\conditions\users;

/**
 * User query condition.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\User\Conditions\UserCondition} instead.
 */
class UserCondition extends \CraftCms\Cms\User\Conditions\UserCondition
{
    use \craft\base\LegacyEventConstants;
}
