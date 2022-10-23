<?php

namespace craft\elements\conditions\assets;

use craft\elements\conditions\ElementCondition;

/**
 * Asset query condition.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class AssetCondition extends ElementCondition
{
    /**
     * @inheritdoc
     */
    protected function conditionRuleTypes(): array
    {
        return array_merge(parent::conditionRuleTypes(), [
            DateModifiedConditionRule::class,
            EditableConditionRule::class,
            FileSizeConditionRule::class,
            FileTypeConditionRule::class,
            FilenameConditionRule::class,
            HasAltConditionRule::class,
            HeightConditionRule::class,
            UploaderConditionRule::class,
            VolumeConditionRule::class,
            WidthConditionRule::class,
        ]);
    }
}
