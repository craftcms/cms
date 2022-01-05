<?php

namespace craft\fields\conditions;

use Craft;
use craft\base\conditions\BaseElementSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use Illuminate\Support\Collection;
use yii\base\InvalidConfigException;

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

    const OPERATOR_RELATED_TO = 'relatedTo';

    /**
     * @inheritdoc
     */
    public string $operator = self::OPERATOR_NOT_EMPTY;

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
    protected bool $reloadOnOperatorChange = true;

    /**
     * @inheritdoc
     */
    public static function supportsProjectConfig(): bool
    {
        return true;
    }

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
    protected function operators(): array
    {
        return array_filter([
            self::OPERATOR_NOT_EMPTY,
            self::OPERATOR_EMPTY,
            !$this->getCondition()->forProjectConfig ? self::OPERATOR_RELATED_TO : null,
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function operatorLabel(string $operator): string
    {
        switch ($operator) {
            case self::OPERATOR_RELATED_TO:
                return Craft::t('app', 'is related to');
            default:
                return parent::operatorLabel($operator);
        }
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(): string
    {
        switch ($this->operator) {
            case self::OPERATOR_RELATED_TO:
                return parent::inputHtml();
            default:
                return '';
        }
    }

    /**
     * @inheritdoc
     */
    protected function elementQueryParam()
    {
        switch ($this->operator) {
            case self::OPERATOR_RELATED_TO:
                return $this->getElementId();
            case self::OPERATOR_EMPTY:
                return ':empty:';
            case self::OPERATOR_NOT_EMPTY:
                return 'not :empty:';
            default:
                throw new InvalidConfigException("Invalid operator: $this->operator");
        }
    }

    /**
     * @inheritdoc
     */
    protected function matchFieldValue($value): bool
    {
        /** @var ElementQueryInterface|Collection $value */
        if ($this->operator === self::OPERATOR_RELATED_TO) {
            $elementIds = $value->collect()->map(fn(ElementInterface $element) => $element->id);
            return $this->matchValue($elementIds);
        }

        if ($value instanceof ElementQueryInterface) {
            $isEmpty = !$value->exists();
        } else {
            $isEmpty = $value->isEmpty();
        }

        if ($this->operator === self::OPERATOR_EMPTY) {
            return $isEmpty;
        }

        return !$isEmpty;
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
