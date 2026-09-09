<?php

declare(strict_types=1);

namespace craft\fields\conditions;

use CraftCms\Cms\Field\Date;
use CraftCms\Yii2Adapter\Form\Concerns\LegacyDateRangeConditionRule;
use RuntimeException;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Field\Conditions\DateFieldConditionRule instead. */
class DateFieldConditionRule extends \CraftCms\Cms\Field\Conditions\DateFieldConditionRule
{
    use LegacyDateRangeConditionRule {
        inputHtml as private baseInputHtml;
    }

    protected function inputHtml(): string
    {
        if (!$this->field() instanceof Date) {
            throw new RuntimeException();
        }

        return $this->baseInputHtml();
    }
}
