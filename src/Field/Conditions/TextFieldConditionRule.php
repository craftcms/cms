<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Conditions;

use CraftCms\Cms\Condition\BaseTextConditionRule;
use CraftCms\Cms\Field\Conditions\Contracts\FieldConditionRuleInterface;

class TextFieldConditionRule extends BaseTextConditionRule implements FieldConditionRuleInterface
{
    use FieldConditionRuleTrait;

    /**
     * {@inheritdoc}
     */
    protected function elementQueryParam(): ?array
    {
        $value = $this->paramValue();
        if ($value === null) {
            return null;
        }

        return [
            'value' => $this->paramValue(),
            'caseInsensitive' => true,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function matchFieldValue($value): bool
    {
        /** @var string|null $value */
        return $this->matchValue($value);
    }
}
