<?php

namespace craft\elements\conditions\entries;

/**
 * Entry type condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Entry\Conditions\TypeConditionRule} instead.
 */
class TypeConditionRule extends \CraftCms\Cms\Entry\Conditions\TypeConditionRule
{
    use \craft\base\LegacyEventConstants;
}
