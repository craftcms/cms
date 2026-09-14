<?php

declare(strict_types=1);

namespace craft\fields\conditions;

use CraftCms\Cms\Field\Lightswitch;
use CraftCms\Yii2Adapter\Form\Concerns\LegacyLightswitchConditionRule;
use RuntimeException;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Field\Conditions\LightswitchFieldConditionRule instead. */
class LightswitchFieldConditionRule extends \CraftCms\Cms\Field\Conditions\LightswitchFieldConditionRule
{
    use LegacyLightswitchConditionRule {
        inputHtml as private baseInputHtml;
    }

    protected function inputHtml(): string
    {
        if (!$this->field() instanceof Lightswitch) {
            throw new RuntimeException();
        }

        return $this->baseInputHtml();
    }
}
