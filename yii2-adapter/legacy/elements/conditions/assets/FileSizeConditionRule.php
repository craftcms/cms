<?php

declare(strict_types=1);

namespace craft\elements\conditions\assets;

use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Support\Html;
use CraftCms\Yii2Adapter\Form\Concerns\LegacyNumberConditionRule;
use function CraftCms\Cms\t;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Asset\Conditions\FileSizeConditionRule instead. */
class FileSizeConditionRule extends \CraftCms\Cms\Asset\Conditions\FileSizeConditionRule
{
    use LegacyNumberConditionRule {
        inputHtml as private baseInputHtml;
    }

    protected function inputHtml(): string
    {
        $unitId = 'unit';

        return Html::tag('div',
            $this->baseInputHtml() .
            Html::hiddenLabel(t('Unit'), $unitId) .
            FormFields::selectHtml([
                'name' => 'unit',
                'id' => $unitId,
                'options' => [
                    ['value' => self::UNIT_B, 'label' => self::UNIT_B],
                    ['value' => self::UNIT_KB, 'label' => self::UNIT_KB],
                    ['value' => self::UNIT_MB, 'label' => self::UNIT_MB],
                    ['value' => self::UNIT_GB, 'label' => self::UNIT_GB],
                ],
                'value' => $this->unit,
            ]),
            [
                'class' => ['flex', 'flex-nowrap'],
            ]
        );
    }
}
