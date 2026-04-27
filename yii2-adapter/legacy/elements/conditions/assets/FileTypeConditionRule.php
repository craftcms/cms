<?php

namespace craft\elements\conditions\assets;

/**
 * Asset volume condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Conditions\FileTypeConditionRule} instead.
 */
class FileTypeConditionRule extends \CraftCms\Cms\Asset\Conditions\FileTypeConditionRule
{
    use \craft\base\LegacyEventConstants;
}
