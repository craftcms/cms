<?php

namespace craft\conditions\elements\fields;

use craft\conditions\BaseElementSelectConditionRule;

/**
 * Relational field condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class RelationalFieldConditionRule extends BaseElementSelectConditionRule implements FieldConditionRuleInterface
{
    use FieldConditionRuleTrait {
        defineRules as private defineFieldRules;
        getConfig as private fieldConfig;
    }

    /**
     * @var string
     * @see elementType()
     */
    public string $elementType;

    /**
     * @var string[]|null
     * @see sources()
     */
    public ?array $sources = null;

    /**
     * @var array|null
     * @see criteria()
     */
    public ?array $criteria = null;

    /**
     * @inheritdoc
     */
    protected function elementType(): string
    {
        return $this->elementType;
    }

    /**
     * @inheritdoc
     */
    protected function sources(): ?array
    {
        return $this->sources;
    }

    /**
     * @inheritdoc
     */
    protected function criteria(): ?array
    {
        return $this->criteria;
    }

    /**
     * @inheritdoc
     */
    protected function elementQueryParam(): ?int
    {
        return $this->getElementId();
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = $this->defineFieldRules();
        $rules[] = [['elementType', 'sources', 'criteria'], 'safe'];
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        return array_merge($this->fieldConfig(), [
            'elementType' => $this->elementType,
            'sources' => $this->sources,
            'criteria' => $this->criteria,
        ]);
    }
}
