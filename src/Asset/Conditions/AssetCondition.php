<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Conditions;

use CraftCms\Cms\Element\Conditions\ElementCondition;

final class AssetCondition extends ElementCondition
{
    #[\Override]
    protected function selectableConditionRules(): array
    {
        return array_merge(parent::selectableConditionRules(), [
            DateModifiedConditionRule::class,
            FileSizeConditionRule::class,
            FileTypeConditionRule::class,
            FilenameConditionRule::class,
            HasAltConditionRule::class,
            HeightConditionRule::class,
            SavableConditionRule::class,
            UploaderConditionRule::class,
            ViewableConditionRule::class,
            VolumeConditionRule::class,
            WidthConditionRule::class,
        ]);
    }
}
