<?php

namespace craft\elements\conditions;

/**
 * Not Relation condition rule.
 *
 * @property int[] $elementIds
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.4.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Conditions\NotRelatedToConditionRule} instead.
 */
class NotRelatedToConditionRule extends \CraftCms\Cms\Element\Conditions\NotRelatedToConditionRule
{
    use \craft\base\LegacyEventConstants;
}
