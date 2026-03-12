<?php

namespace craft\elements\conditions;

/**
 * Relation condition rule.
 *
 * @property int[] $elementIds
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Conditions\RelatedToConditionRule} instead.
 */
class RelatedToConditionRule extends \CraftCms\Cms\Element\Conditions\RelatedToConditionRule
{
    use \craft\base\LegacyEventConstants;
}
