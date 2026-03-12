<?php

namespace craft\elements\conditions;

/**
 * Element level condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Conditions\LevelConditionRule} instead.
 */
class LevelConditionRule extends \CraftCms\Cms\Element\Conditions\LevelConditionRule
{
    use \craft\base\LegacyEventConstants;
}
