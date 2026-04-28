<?php

namespace craft\elements\conditions\entries;

/**
 * Entry query condition.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Entry\Conditions\EntryCondition} instead.
 */
class EntryCondition extends \CraftCms\Cms\Entry\Conditions\EntryCondition
{
    use \craft\base\LegacyEventConstants;
}
