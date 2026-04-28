<?php

namespace craft\elements\conditions\assets;

/**
 * File Size condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Conditions\FileSizeConditionRule} instead.
 */
class FileSizeConditionRule extends \CraftCms\Cms\Asset\Conditions\FileSizeConditionRule
{
    use \craft\base\LegacyEventConstants;
}
