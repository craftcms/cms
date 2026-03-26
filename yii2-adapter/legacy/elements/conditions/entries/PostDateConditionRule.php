<?php

namespace craft\elements\conditions\entries;

/**
 * Element post date condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Entry\Conditions\PostDateConditionRule} instead.
 */
class PostDateConditionRule extends \CraftCms\Cms\Entry\Conditions\PostDateConditionRule
{
    use \craft\base\LegacyEventConstants;
}
