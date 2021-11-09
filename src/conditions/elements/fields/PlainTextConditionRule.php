<?php

namespace craft\conditions\elements\fields;

use craft\conditions\BaseTextConditionRule;
use craft\conditions\elements\fields\FieldConditionRuleInterface;
use craft\conditions\elements\fields\FieldConditionRuleTrait;

/**
 * Plain Text field condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class PlainTextConditionRule extends BaseTextConditionRule implements FieldConditionRuleInterface
{
    use FieldConditionRuleTrait;

    /**
     * @inheritdoc
     */
    protected function elementQueryParam(): ?string
    {
        return $this->paramValue();
    }
}
