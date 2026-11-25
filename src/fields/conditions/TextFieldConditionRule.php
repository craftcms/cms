<?php

namespace craft\fields\conditions;

use craft\base\conditions\BaseTextConditionRule;

/**
 * Text field condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class TextFieldConditionRule extends BaseTextConditionRule implements FieldConditionRuleInterface
{
    use FieldConditionRuleTrait;

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    protected function matchFieldValue($value): bool
    {
        /** @var string|null $value */
        return $this->matchValue($value);
    }
}
