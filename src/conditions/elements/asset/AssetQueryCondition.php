<?php

namespace craft\conditions\elements\asset;

use craft\conditions\elements\ElementQueryCondition;

/**
 * Asset query condition.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class AssetQueryCondition extends ElementQueryCondition
{
    /**
     * @inheritdoc
     */
    protected function conditionRuleTypes(): array
    {
        return array_merge(parent::conditionRuleTypes(), [
            DateModifiedConditionRule::class,
            FileSizeConditionRule::class,
            FileTypeConditionRule::class,
            FilenameConditionRule::class,
            HeightConditionRule::class,
            UploaderConditionRule::class,
            VolumeConditionRule::class,
            WidthConditionRule::class,
        ]);
    }
}
