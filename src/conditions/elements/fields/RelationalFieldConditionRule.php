<?php

namespace craft\conditions\elements\fields;

use craft\conditions\elements\RelatedToConditionRule;

/**
 * Relational field condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class RelationalFieldConditionRule extends RelatedToConditionRule implements FieldConditionRuleInterface
{
    use FieldConditionRuleTrait;

    /**
     * @inheritdoc
     */
    protected function elementQueryParam(): array
    {
        return $this->getElementIds();
    }

    /**
     * @inheritdochandleException
     */
    public function getHtml(array $options = []): string
    {
        return $this->elementSelectHtml();
    }
}
