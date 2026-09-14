<?php

declare(strict_types=1);

namespace craft\fields\conditions;

use CraftCms\Cms\Field\BaseRelationField;
use CraftCms\Yii2Adapter\Form\Concerns\LegacyElementSelectConditionRule;
use RuntimeException;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Field\Conditions\RelationalFieldConditionRule instead. */
class RelationalFieldConditionRule extends \CraftCms\Cms\Field\Conditions\RelationalFieldConditionRule
{
    use LegacyElementSelectConditionRule {
        inputHtml as private baseInputHtml;
    }

    protected function inputHtml(): string
    {
        if (!$this->field() instanceof BaseRelationField) {
            throw new RuntimeException();
        }

        return match ($this->operator) {
            self::OPERATOR_RELATED_TO => $this->baseInputHtml(),
            default => '',
        };
    }
}
