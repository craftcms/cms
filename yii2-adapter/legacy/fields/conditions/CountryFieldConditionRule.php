<?php

declare(strict_types=1);

namespace craft\fields\conditions;

use CraftCms\Cms\Field\Country;
use CraftCms\Yii2Adapter\Form\Concerns\LegacyMultiSelectConditionRule;
use RuntimeException;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Field\Conditions\CountryFieldConditionRule instead. */
class CountryFieldConditionRule extends \CraftCms\Cms\Field\Conditions\CountryFieldConditionRule
{
    use LegacyMultiSelectConditionRule {
        inputHtml as private baseInputHtml;
    }

    protected function inputHtml(): string
    {
        if (!$this->field() instanceof Country) {
            throw new RuntimeException();
        }

        return $this->baseInputHtml();
    }
}
