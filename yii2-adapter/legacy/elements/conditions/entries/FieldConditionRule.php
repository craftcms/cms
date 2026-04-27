<?php

namespace craft\elements\conditions\entries;

/**
 * Field condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.6.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Entry\Conditions\FieldConditionRule} instead.
 */
class FieldConditionRule extends \CraftCms\Cms\Entry\Conditions\FieldConditionRule
{
    use \craft\base\LegacyEventConstants;
}
